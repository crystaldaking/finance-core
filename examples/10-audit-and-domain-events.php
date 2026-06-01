<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Crystal\Finance\Core\Audit\Actor;
use Crystal\Finance\Core\Audit\AuditContext;
use Crystal\Finance\Core\Event\EventId;
use Crystal\Finance\Core\Event\EventMetadata;
use Crystal\Finance\Core\Event\GenericDomainEvent;
use Crystal\Finance\Core\Event\RecordsEvents;

final class ExampleOutboxAggregate
{
    use RecordsEvents;

    public function record(GenericDomainEvent $event): void
    {
        $this->recordThat($event);
    }
}

$occurredAt = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
$audit = AuditContext::record(
    Actor::system(),
    'Nightly reconciliation posted a correction transaction',
    $occurredAt,
    metadata: ['job' => 'nightly_reconciliation'],
);

$event = GenericDomainEvent::record(
    'Ledger.TransactionPosted',
    'ltx_reconciliation_001',
    $occurredAt,
    EventMetadata::fromArray(['balanced' => true]),
    $audit,
    EventId::fromString('evt_reconciliation_001'),
);
$eventAuditContext = $event->auditContext() ?? throw new RuntimeException('Event audit context is missing.');

$outbox = new ExampleOutboxAggregate();
$outbox->record($event);
$releasedEvents = $outbox->releaseEvents();

echo 'Event: ' . $event->eventName() . PHP_EOL;
echo 'Audit reason: ' . $eventAuditContext->reason() . PHP_EOL;
echo 'Outbox events: ' . count($releasedEvents) . PHP_EOL;
