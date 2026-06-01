<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Audit;

use Crystal\Finance\Core\Exception\InvalidAuditContext;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final readonly class AuditContext
{
    /**
     * @param array<string, string|int|bool|null> $metadata
     */
    private function __construct(
        private Actor $actor,
        private string $reason,
        private DateTimeImmutable $occurredAt,
        private ?CorrelationId $correlationId,
        private ?CausationId $causationId,
        private ?RequestId $requestId,
        private array $metadata,
    ) {
        if ($reason === '') {
            throw InvalidAuditContext::emptyReason();
        }
    }

    /**
     * @param array<string, string|int|bool|null> $metadata
     */
    public static function record(
        Actor $actor,
        string $reason,
        DateTimeImmutable $occurredAt,
        ?CorrelationId $correlationId = null,
        ?CausationId $causationId = null,
        ?RequestId $requestId = null,
        array $metadata = [],
    ): self {
        return new self($actor, $reason, $occurredAt, $correlationId, $causationId, $requestId, $metadata);
    }

    /**
     * @param array<string, string|int|bool|null> $metadata
     */
    public static function fromClock(Actor $actor, string $reason, ClockInterface $clock, array $metadata = []): self
    {
        return new self($actor, $reason, $clock->now(), null, null, null, $metadata);
    }

    public function actor(): Actor
    {
        return $this->actor;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function correlationId(): ?CorrelationId
    {
        return $this->correlationId;
    }

    public function causationId(): ?CausationId
    {
        return $this->causationId;
    }

    public function requestId(): ?RequestId
    {
        return $this->requestId;
    }

    /**
     * @return array<string, string|int|bool|null>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
