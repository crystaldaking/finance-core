<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Idempotency;

use DateTimeImmutable;
use Throwable;

interface IdempotencyStore
{
    public function find(IdempotencyScope $scope, IdempotencyKey $key): ?IdempotencyRecord;

    /**
     * Implementations must provide atomic create-if-absent semantics for active records.
     *
     * Expired records are claimable again. A store may either hide expired records from find()
     * or atomically replace them during begin().
     */
    public function begin(
        IdempotencyScope $scope,
        IdempotencyKey $key,
        PayloadFingerprint $fingerprint,
        DateTimeImmutable $expiresAt,
    ): IdempotencyRecord;

    /**
     * Atomically complete the active claim represented by the record.
     *
     * The runner refreshes expiresAt before this call so it represents replay retention.
     * Implementations must compare claimId and not overwrite a newer claim for the same scope and key.
     */
    public function complete(IdempotencyRecord $record, mixed $result): IdempotencyRecord;

    /**
     * Atomically fail the active claim after comparing claimId, without overwriting a newer claim.
     */
    public function fail(IdempotencyRecord $record, Throwable $throwable): IdempotencyRecord;
}
