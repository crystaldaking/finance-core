<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Idempotency;

use Crystal\Finance\Core\Exception\IdempotencyConflict;
use DateInterval;
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
        $existing = $this->store->find($scope, $key);

        if ($existing !== null && !$existing->isExpired($this->clock->now())) {
            if (!$existing->fingerprint()->equals($fingerprint)) {
                throw IdempotencyConflict::fingerprintMismatch($scope->value(), $key->value());
            }

            if ($existing->status() === IdempotencyStatus::Completed) {
                return IdempotencyResult::replay($existing);
            }

            throw IdempotencyConflict::alreadyStarted($scope->value(), $key->value());
        }

        $record = $this->store->begin($scope, $key, $fingerprint, $this->clock->now()->add($ttl));

        try {
            $result = $callback();
        } catch (Throwable $throwable) {
            $this->store->fail($record, $throwable);

            throw $throwable;
        }

        return IdempotencyResult::fresh($this->store->complete($record, $result));
    }
}
