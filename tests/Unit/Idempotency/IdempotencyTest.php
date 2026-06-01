<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Idempotency;

use Crystal\Finance\Core\Exception\IdempotencyConflict;
use Crystal\Finance\Core\Exception\InvalidIdempotencyKey;
use Crystal\Finance\Core\Exception\InvalidIdempotencyScope;
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
        $store = new InMemoryIdempotencyStore();
        $clock = new FixedClock(new DateTimeImmutable('2026-01-01T00:00:00+00:00'));
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
        $store = new InMemoryIdempotencyStore();
        $clock = new FixedClock(new DateTimeImmutable('2026-01-01T00:00:00+00:00'));
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
