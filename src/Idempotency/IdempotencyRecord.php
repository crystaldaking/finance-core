<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Idempotency;

use DateTimeImmutable;

final readonly class IdempotencyRecord
{
    private string $claimId;

    public function __construct(
        private IdempotencyScope $scope,
        private IdempotencyKey $key,
        private PayloadFingerprint $fingerprint,
        private IdempotencyStatus $status,
        private DateTimeImmutable $expiresAt,
        private mixed $result = null,
        private ?string $failureClass = null,
        ?string $claimId = null,
    ) {
        $this->claimId = $claimId ?? 'idc_' . bin2hex(random_bytes(16));
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

    public function claimId(): string
    {
        return $this->claimId;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now || $this->status === IdempotencyStatus::Expired;
    }

    public function withExpiresAt(DateTimeImmutable $expiresAt): self
    {
        return new self(
            $this->scope,
            $this->key,
            $this->fingerprint,
            $this->status,
            $expiresAt,
            $this->result,
            $this->failureClass,
            $this->claimId,
        );
    }
}
