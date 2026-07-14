<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Fixtures;

use Crystal\Finance\Core\Exception\IdempotencyConflict;
use Crystal\Finance\Core\Idempotency\IdempotencyKey;
use Crystal\Finance\Core\Idempotency\IdempotencyRecord;
use Crystal\Finance\Core\Idempotency\IdempotencyScope;
use Crystal\Finance\Core\Idempotency\IdempotencyStatus;
use Crystal\Finance\Core\Idempotency\IdempotencyStore;
use Crystal\Finance\Core\Idempotency\PayloadFingerprint;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Throwable;

final class InMemoryIdempotencyStore implements IdempotencyStore
{
    /**
     * @var array<string, IdempotencyRecord>
     */
    private array $records = [];

    public function __construct(private ?ClockInterface $clock = null)
    {
    }

    #[\Override]
    public function find(IdempotencyScope $scope, IdempotencyKey $key): ?IdempotencyRecord
    {
        return $this->records[$this->recordKey($scope, $key)] ?? null;
    }

    #[\Override]
    public function begin(
        IdempotencyScope $scope,
        IdempotencyKey $key,
        PayloadFingerprint $fingerprint,
        DateTimeImmutable $expiresAt,
    ): IdempotencyRecord {
        $recordKey = $this->recordKey($scope, $key);
        $existing = $this->records[$recordKey] ?? null;

        if ($existing !== null && !$this->isExpired($existing)) {
            if (!$existing->fingerprint()->equals($fingerprint)) {
                throw IdempotencyConflict::fingerprintMismatch($scope->value(), $key->value());
            }

            throw IdempotencyConflict::alreadyStarted($scope->value(), $key->value());
        }

        $record = new IdempotencyRecord($scope, $key, $fingerprint, IdempotencyStatus::Started, $expiresAt);
        $this->records[$recordKey] = $record;

        return $record;
    }

    #[\Override]
    public function complete(IdempotencyRecord $record, mixed $result): IdempotencyRecord
    {
        $this->assertActiveClaim($record);

        $completed = new IdempotencyRecord(
            $record->scope(),
            $record->key(),
            $record->fingerprint(),
            IdempotencyStatus::Completed,
            $record->expiresAt(),
            $result,
            claimId: $record->claimId(),
        );
        $this->records[$this->recordKey($record->scope(), $record->key())] = $completed;

        return $completed;
    }

    #[\Override]
    public function fail(IdempotencyRecord $record, Throwable $throwable): IdempotencyRecord
    {
        $this->assertActiveClaim($record);

        $failed = new IdempotencyRecord(
            $record->scope(),
            $record->key(),
            $record->fingerprint(),
            IdempotencyStatus::Failed,
            $record->expiresAt(),
            null,
            $throwable::class,
            $record->claimId(),
        );
        $this->records[$this->recordKey($record->scope(), $record->key())] = $failed;

        return $failed;
    }

    private function recordKey(IdempotencyScope $scope, IdempotencyKey $key): string
    {
        return $scope->value() . ':' . $key->value();
    }

    private function isExpired(IdempotencyRecord $record): bool
    {
        return $this->clock !== null && $record->isExpired($this->clock->now());
    }

    private function assertActiveClaim(IdempotencyRecord $record): void
    {
        $current = $this->records[$this->recordKey($record->scope(), $record->key())] ?? null;

        if ($current === null
            || $current->status() !== IdempotencyStatus::Started
            || !hash_equals($current->claimId(), $record->claimId())
        ) {
            throw IdempotencyConflict::staleClaim($record->scope()->value(), $record->key()->value());
        }
    }
}
