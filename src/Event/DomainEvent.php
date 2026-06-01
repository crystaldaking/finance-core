<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Event;

use Crystal\Finance\Core\Audit\AuditContext;
use DateTimeImmutable;

interface DomainEvent
{
    public function eventId(): EventId;

    public function eventName(): string;

    public function occurredAt(): DateTimeImmutable;

    public function aggregateId(): string;

    public function metadata(): EventMetadata;

    public function auditContext(): ?AuditContext;
}
