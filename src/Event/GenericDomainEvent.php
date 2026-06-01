<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Event;

use Crystal\Finance\Core\Audit\AuditContext;
use Crystal\Finance\Core\Exception\InvalidDomainEvent;
use DateTimeImmutable;

final readonly class GenericDomainEvent implements DomainEvent
{
    private function __construct(
        private EventId $eventId,
        private string $eventName,
        private DateTimeImmutable $occurredAt,
        private string $aggregateId,
        private EventMetadata $metadata,
        private ?AuditContext $auditContext,
    ) {
        if (preg_match('/^[A-Z][A-Za-z0-9.]{1,127}$/', $eventName) !== 1) {
            throw InvalidDomainEvent::invalidName($eventName);
        }

        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$/', $aggregateId) !== 1) {
            throw InvalidDomainEvent::invalidAggregateId($aggregateId);
        }
    }

    public static function record(
        string $eventName,
        string $aggregateId,
        DateTimeImmutable $occurredAt,
        ?EventMetadata $metadata = null,
        ?AuditContext $auditContext = null,
        ?EventId $eventId = null,
    ): self {
        return new self(
            $eventId ?? EventId::generate(),
            $eventName,
            $occurredAt,
            $aggregateId,
            $metadata ?? EventMetadata::empty(),
            $auditContext,
        );
    }

    #[\Override]
    public function eventId(): EventId
    {
        return $this->eventId;
    }

    #[\Override]
    public function eventName(): string
    {
        return $this->eventName;
    }

    #[\Override]
    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    #[\Override]
    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    #[\Override]
    public function metadata(): EventMetadata
    {
        return $this->metadata;
    }

    #[\Override]
    public function auditContext(): ?AuditContext
    {
        return $this->auditContext;
    }
}
