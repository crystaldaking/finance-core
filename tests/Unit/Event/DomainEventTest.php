<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Event;

use Crystal\Finance\Core\Event\DomainEvent;
use Crystal\Finance\Core\Event\EventId;
use Crystal\Finance\Core\Event\EventMetadata;
use Crystal\Finance\Core\Event\GenericDomainEvent;
use Crystal\Finance\Core\Event\RecordsEvents;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DomainEventTest extends TestCase
{
    public function testGenericEventCarriesMetadata(): void
    {
        $event = GenericDomainEvent::record(
            'Ledger.TransactionPosted',
            'ltx_123',
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            EventMetadata::fromArray(['balanced' => true]),
            eventId: EventId::fromString('evt_123'),
        );

        self::assertSame('evt_123', $event->eventId()->value());
        self::assertSame('Ledger.TransactionPosted', $event->eventName());
        self::assertSame('ltx_123', $event->aggregateId());
        self::assertSame(['balanced' => true], $event->metadata()->toArray());
    }

    public function testRecordsEventsReleasesAndClearsEvents(): void
    {
        $aggregate = new EventRecordingFixture();
        $event = GenericDomainEvent::record(
            'Ledger.TransactionPosted',
            'ltx_123',
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );

        $aggregate->record($event);

        self::assertCount(1, $aggregate->releaseEvents());
        self::assertCount(0, $aggregate->releaseEvents());
    }
}

final class EventRecordingFixture
{
    use RecordsEvents;

    public function record(DomainEvent $event): void
    {
        $this->recordThat($event);
    }
}
