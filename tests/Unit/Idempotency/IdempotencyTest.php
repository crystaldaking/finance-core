<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Idempotency;

use Crystal\Finance\Core\Exception\IdempotencyConflict;
use Crystal\Finance\Core\Exception\InvalidIdempotencyKey;
use Crystal\Finance\Core\Exception\InvalidIdempotencyScope;
use Crystal\Finance\Core\Exception\InvalidIdempotencyTtl;
use Crystal\Finance\Core\Exception\InvalidPayloadFingerprint;
use Crystal\Finance\Core\Idempotency\IdempotencyKey;
use Crystal\Finance\Core\Idempotency\IdempotencyRunner;
use Crystal\Finance\Core\Idempotency\IdempotencyScope;
use Crystal\Finance\Core\Idempotency\IdempotencyStatus;
use Crystal\Finance\Core\Idempotency\PayloadFingerprint;
use Crystal\Finance\Core\Tests\Fixtures\FixedClock;
use Crystal\Finance\Core\Tests\Fixtures\InMemoryIdempotencyStore;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class IdempotencyTest extends TestCase
{
    public function testFingerprintIsStableRegardlessOfObjectKeyOrder(): void
    {
        $first = PayloadFingerprint::fromArray([
            'amount' => '100.00',
            'asset' => 'EUR',
            'metadata' => [
                'b' => 'second',
                'a' => 'first',
            ],
        ]);

        $second = PayloadFingerprint::fromArray([
            'metadata' => [
                'a' => 'first',
                'b' => 'second',
            ],
            'asset' => 'EUR',
            'amount' => '100.00',
        ]);

        self::assertTrue($first->equals($second));
    }

    public function testFingerprintChangesWhenPayloadChanges(): void
    {
        $first = PayloadFingerprint::fromArray(['amount' => '100.00']);
        $second = PayloadFingerprint::fromArray(['amount' => '100.01']);

        self::assertFalse($first->equals($second));
    }

    public function testFingerprintCanBeCreatedFromString(): void
    {
        $fingerprint = PayloadFingerprint::fromString('sha256:' . str_repeat('a', 64));

        self::assertSame('sha256:' . str_repeat('a', 64), $fingerprint->value());
    }

    public function testInvalidFingerprintStringIsRejected(): void
    {
        $this->expectException(InvalidPayloadFingerprint::class);

        PayloadFingerprint::fromString('md5:' . str_repeat('a', 32));
    }

    public function testFingerprintRejectsFloatingPointPayloadValues(): void
    {
        $this->expectException(InvalidPayloadFingerprint::class);

        PayloadFingerprint::fromArray(['amount' => 10.25]);
    }

    public function testInvalidKeyIsRejected(): void
    {
        $this->expectException(InvalidIdempotencyKey::class);

        IdempotencyKey::fromString('short');
    }

    public function testInvalidScopeIsRejected(): void
    {
        $this->expectException(InvalidIdempotencyScope::class);

        IdempotencyScope::fromString('Payment Scope');
    }

    public function testRunnerCompletesAndReplaysSameFingerprint(): void
    {
        $runner = $this->runner();
        $scope = IdempotencyScope::fromString('ledger:append');
        $key = IdempotencyKey::fromString('key_12345678');
        $fingerprint = PayloadFingerprint::fromArray(['amount' => '100.00']);

        $first = $runner->run($scope, $key, $fingerprint, new DateInterval('PT1H'), static fn (): string => 'posted');
        $second = $runner->run($scope, $key, $fingerprint, new DateInterval('PT1H'), static fn (): string => 'not-used');

        self::assertFalse($first->replayed());
        self::assertTrue($second->replayed());
        self::assertSame('posted', $second->result());
        self::assertSame(IdempotencyStatus::Completed, $second->record()->status());
        self::assertSame($first->record()->claimId(), $second->record()->claimId());
    }

    public function testRunnerRejectsSameKeyWithDifferentFingerprint(): void
    {
        $runner = $this->runner();
        $scope = IdempotencyScope::fromString('ledger:append');
        $key = IdempotencyKey::fromString('key_12345678');

        $runner->run(
            $scope,
            $key,
            PayloadFingerprint::fromArray(['amount' => '100.00']),
            new DateInterval('PT1H'),
            static fn (): string => 'posted',
        );

        $this->expectException(IdempotencyConflict::class);

        $runner->run(
            $scope,
            $key,
            PayloadFingerprint::fromArray(['amount' => '100.01']),
            new DateInterval('PT1H'),
            static fn (): string => 'not-used',
        );
    }

    public function testFailedCallbackStoresFailedStatusAndRethrows(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-01-01T00:00:00+00:00'));
        $store = new InMemoryIdempotencyStore($clock);
        $runner = new IdempotencyRunner($store, $clock);
        $scope = IdempotencyScope::fromString('ledger:append');
        $key = IdempotencyKey::fromString('key_failure_1');
        $fingerprint = PayloadFingerprint::fromArray(['amount' => '100.00']);

        try {
            $runner->run(
                $scope,
                $key,
                $fingerprint,
                new DateInterval('PT1H'),
                self::failingCallback(...),
            );
        } catch (RuntimeException) {
        }

        $record = $store->find($scope, $key);

        self::assertNotNull($record);
        self::assertSame(IdempotencyStatus::Failed, $record->status());
        self::assertSame(RuntimeException::class, $record->failureClass());
    }

    public function testExpiredRecordCanBeStartedAgain(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-01-01T00:00:00+00:00'));
        $store = new InMemoryIdempotencyStore($clock);
        $runner = new IdempotencyRunner($store, $clock);
        $scope = IdempotencyScope::fromString('ledger:append');
        $key = IdempotencyKey::fromString('key_expired_1');
        $fingerprint = PayloadFingerprint::fromArray(['amount' => '100.00']);

        $runner->run($scope, $key, $fingerprint, new DateInterval('PT1S'), static fn (): string => 'first');
        $clock->moveTo(new DateTimeImmutable('2026-01-01T00:00:02+00:00'));

        $result = $runner->run($scope, $key, $fingerprint, new DateInterval('PT1H'), static fn (): string => 'second');

        self::assertFalse($result->replayed());
        self::assertSame('second', $result->result());
    }

    public function testRunnerRejectsZeroTtlBeforeExecutingCallback(): void
    {
        $executions = 0;

        try {
            $this->runner()->run(
                IdempotencyScope::fromString('ledger:append'),
                IdempotencyKey::fromString('key_zero_ttl'),
                PayloadFingerprint::fromArray(['amount' => '100.00']),
                new DateInterval('PT0S'),
                static function () use (&$executions): void {
                    $executions++;
                },
            );
            self::fail('Expected zero TTL to be rejected.');
        } catch (InvalidIdempotencyTtl) {
        }

        self::assertSame(0, $executions);
    }

    public function testRunnerRejectsNegativeTtl(): void
    {
        $ttl = new DateInterval('PT1S');
        $ttl->invert = 1;

        $this->expectException(InvalidIdempotencyTtl::class);

        $this->runner()->run(
            IdempotencyScope::fromString('ledger:append'),
            IdempotencyKey::fromString('key_negative_ttl'),
            PayloadFingerprint::fromArray(['amount' => '100.00']),
            $ttl,
            static fn (): string => 'not-used',
        );
    }

    public function testCompletedReplayTtlStartsWhenCallbackFinishes(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-01-01T00:00:00+00:00'));
        $store = new InMemoryIdempotencyStore($clock);
        $runner = new IdempotencyRunner($store, $clock);
        $scope = IdempotencyScope::fromString('ledger:append');
        $key = IdempotencyKey::fromString('key_long_callback');
        $fingerprint = PayloadFingerprint::fromArray(['amount' => '100.00']);
        $executions = 0;

        $runner->run(
            $scope,
            $key,
            $fingerprint,
            new DateInterval('PT1S'),
            static function () use (&$executions, $clock): string {
                $executions++;
                $clock->moveTo(new DateTimeImmutable('2026-01-01T00:00:02+00:00'));

                return 'posted';
            },
        );
        $replay = $runner->run(
            $scope,
            $key,
            $fingerprint,
            new DateInterval('PT1S'),
            static function () use (&$executions): string {
                $executions++;

                return 'not-used';
            },
        );

        self::assertTrue($replay->replayed());
        self::assertSame(1, $executions);
        self::assertSame('2026-01-01T00:00:03+00:00', $replay->record()->expiresAt()->format(DATE_ATOM));
    }

    public function testStaleCompletionAndFailureCannotOverwriteANewerClaim(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-01-01T00:00:00+00:00'));
        $store = new InMemoryIdempotencyStore($clock);
        $scope = IdempotencyScope::fromString('ledger:append');
        $key = IdempotencyKey::fromString('key_claim_fencing');
        $fingerprint = PayloadFingerprint::fromArray(['amount' => '100.00']);
        $stale = $store->begin(
            $scope,
            $key,
            $fingerprint,
            new DateTimeImmutable('2026-01-01T00:00:01+00:00'),
        );

        $clock->moveTo(new DateTimeImmutable('2026-01-01T00:00:02+00:00'));
        $current = $store->begin(
            $scope,
            $key,
            $fingerprint,
            new DateTimeImmutable('2026-01-01T01:00:02+00:00'),
        );
        $conflicts = 0;

        try {
            $store->complete(
                $stale->withExpiresAt(new DateTimeImmutable('2026-01-01T01:00:00+00:00')),
                'stale-result',
            );
        } catch (IdempotencyConflict) {
            $conflicts++;
        }

        try {
            $store->fail($stale, new RuntimeException('stale-failure'));
        } catch (IdempotencyConflict) {
            $conflicts++;
        }

        $stored = $store->find($scope, $key);
        self::assertNotNull($stored);
        self::assertSame(2, $conflicts);
        self::assertSame($current->claimId(), $stored->claimId());
        self::assertSame(IdempotencyStatus::Started, $stored->status());
    }

    private function runner(): IdempotencyRunner
    {
        return new IdempotencyRunner(
            new InMemoryIdempotencyStore(),
            new FixedClock(new DateTimeImmutable('2026-01-01T00:00:00+00:00')),
        );
    }

    private static function failingCallback(): string
    {
        throw new RuntimeException('boom');
    }
}
