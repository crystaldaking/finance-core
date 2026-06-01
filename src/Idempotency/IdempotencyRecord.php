<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Idempotency;

use DateTimeImmutable;

final readonly class IdempotencyRecord
{
    public function __construct(
        private IdempotencyScope $scope,
        private IdempotencyKey $key,
        private PayloadFingerprint $fingerprint,
        private IdempotencyStatus $status,
        private DateTimeImmutable $expiresAt,
        private mixed $result = null,
        private ?string $failureClass = null,
    ) {
    }

    public function scope(): IdempotencyScope
    {
        return $this->scope;
    }

    public function key(): IdempotencyKey
    {
        return $this->key;
    }

    public function fingerprint(): PayloadFingerprint
    {
        return $this->fingerprint;
    }

    public function status(): IdempotencyStatus
    {
        return $this->status;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function result(): mixed
    {
        return $this->result;
    }

    public function failureClass(): ?string
    {
        return $this->failureClass;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now || $this->status === IdempotencyStatus::Expired;
    }
}
