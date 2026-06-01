<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Idempotency;

use DateTimeImmutable;
use Throwable;

interface IdempotencyStore
{
    public function find(IdempotencyScope $scope, IdempotencyKey $key): ?IdempotencyRecord;

    /**
     * Implementations must provide atomic create-if-absent semantics for the scope/key pair.
     */
    public function begin(
        IdempotencyScope $scope,
        IdempotencyKey $key,
        PayloadFingerprint $fingerprint,
        DateTimeImmutable $expiresAt,
    ): IdempotencyRecord;

    public function complete(IdempotencyRecord $record, mixed $result): IdempotencyRecord;

    public function fail(IdempotencyRecord $record, Throwable $throwable): IdempotencyRecord;
}
