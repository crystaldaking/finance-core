<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Idempotency;

use Crystal\Finance\Core\Exception\IdempotencyConflict;
use Crystal\Finance\Core\Exception\InvalidIdempotencyTtl;
use DateInterval;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Throwable;

final readonly class IdempotencyRunner
{
    public function __construct(
        private IdempotencyStore $store,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @template T
     * @param callable(): T $callback
     */
    public function run(
        IdempotencyScope $scope,
        IdempotencyKey $key,
        PayloadFingerprint $fingerprint,
        DateInterval $ttl,
        callable $callback,
    ): IdempotencyResult {
        $now = $this->clock->now();
        $leaseExpiresAt = self::expiresAfter($now, $ttl);
        $existing = $this->store->find($scope, $key);

        if ($existing !== null && !$existing->isExpired($now)) {
            if (!$existing->fingerprint()->equals($fingerprint)) {
                throw IdempotencyConflict::fingerprintMismatch($scope->value(), $key->value());
            }

            if ($existing->status() === IdempotencyStatus::Completed) {
                return IdempotencyResult::replay($existing);
            }

            throw IdempotencyConflict::alreadyStarted($scope->value(), $key->value());
        }

        $record = $this->store->begin($scope, $key, $fingerprint, $leaseExpiresAt);

        try {
            $result = $callback();
        } catch (Throwable $throwable) {
            $this->store->fail($record, $throwable);

            throw $throwable;
        }

        $replayExpiresAt = self::expiresAfter($this->clock->now(), $ttl);
        $completed = $this->store->complete($record->withExpiresAt($replayExpiresAt), $result);

        return IdempotencyResult::fresh($completed);
    }

    private static function expiresAfter(DateTimeImmutable $now, DateInterval $ttl): DateTimeImmutable
    {
        $expiresAt = $now->add($ttl);

        if ($expiresAt <= $now) {
            throw InvalidIdempotencyTtl::nonPositive();
        }

        return $expiresAt;
    }
}
