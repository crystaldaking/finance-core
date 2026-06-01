<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Invariants;

use Crystal\Finance\Core\Exception\InvalidPayloadFingerprint;
use Crystal\Finance\Core\Idempotency\IdempotencyKey;
use Crystal\Finance\Core\Idempotency\IdempotencyRunner;
use Crystal\Finance\Core\Idempotency\IdempotencyScope;
use Crystal\Finance\Core\Idempotency\PayloadFingerprint;
use Crystal\Finance\Core\Tests\Fixtures\FixedClock;
use Crystal\Finance\Core\Tests\Fixtures\InMemoryIdempotencyStore;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class IdempotencyInvariantTest extends TestCase
{
    public function testCanonicalFingerprintIgnoresNestedObjectOrderButPreservesListOrder(): void
    {
        $first = PayloadFingerprint::fromArray([
            'entries' => [
                ['account' => 'cash', 'amount' => '100.00'],
                ['amount' => '100.00', 'account' => 'revenue'],
            ],
            'metadata' => [
                'b' => 'second',
                'a' => 'first',
            ],
        ]);

        $sameObjectOrderChanged = PayloadFingerprint::fromArray([
            'metadata' => [
                'a' => 'first',
                'b' => 'second',
            ],
            'entries' => [
                ['amount' => '100.00', 'account' => 'cash'],
                ['account' => 'revenue', 'amount' => '100.00'],
            ],
        ]);

        $listOrderChanged = PayloadFingerprint::fromArray([
            'entries' => [
                ['account' => 'revenue', 'amount' => '100.00'],
                ['account' => 'cash', 'amount' => '100.00'],
            ],
            'metadata' => [
                'a' => 'first',
                'b' => 'second',
            ],
        ]);

        self::assertTrue($first->equals($sameObjectOrderChanged));
        self::assertFalse($first->equals($listOrderChanged));
    }

    public function testFloatingPointPayloadsAreRejectedAtAnyDepth(): void
    {
        $this->expectException(InvalidPayloadFingerprint::class);

        PayloadFingerprint::fromArray([
            'amount' => [
                'decimal' => 10.25,
            ],
        ]);
    }

    public function testReplayDoesNotReexecuteTheCallback(): void
    {
        $executions = 0;
        $runner = $this->runner(new FixedClock(new DateTimeImmutable('2026-01-01T00:00:00+00:00')));
        $scope = IdempotencyScope::fromString('ledger:append');
        $key = IdempotencyKey::fromString('invariant_replay_key');
        $fingerprint = PayloadFingerprint::fromArray(['reference' => 'ledger_1']);

        $callback = static function () use (&$executions): string {
            $executions++;

            return 'posted';
        };

        $first = $runner->run($scope, $key, $fingerprint, new DateInterval('PT1H'), $callback);
        $second = $runner->run($scope, $key, $fingerprint, new DateInterval('PT1H'), $callback);

        self::assertFalse($first->replayed());
        self::assertTrue($second->replayed());
        self::assertSame(1, $executions);
        self::assertSame('posted', $second->result());
    }

    public function testExpiredKeysCanBeClaimedWithANewFingerprint(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-01-01T00:00:00+00:00'));
        $runner = $this->runner($clock);
        $scope = IdempotencyScope::fromString('ledger:append');
        $key = IdempotencyKey::fromString('invariant_expired_key');

        $runner->run(
            $scope,
            $key,
            PayloadFingerprint::fromArray(['reference' => 'first']),
            new DateInterval('PT1S'),
            static fn (): string => 'first-result',
        );

        $clock->moveTo(new DateTimeImmutable('2026-01-01T00:00:02+00:00'));

        $second = $runner->run(
            $scope,
            $key,
            PayloadFingerprint::fromArray(['reference' => 'second']),
            new DateInterval('PT1H'),
            static fn (): string => 'second-result',
        );

        self::assertFalse($second->replayed());
        self::assertSame('second-result', $second->result());
    }

    private function runner(FixedClock $clock): IdempotencyRunner
    {
        return new IdempotencyRunner(new InMemoryIdempotencyStore($clock), $clock);
    }
}
