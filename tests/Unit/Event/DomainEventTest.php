<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Event;

use Crystal\Finance\Core\Event\DomainEvent;
use Crystal\Finance\Core\Event\EventId;
use Crystal\Finance\Core\Event\EventMetadata;
use Crystal\Finance\Core\Event\GenericDomainEvent;
use Crystal\Finance\Core\Event\RecordsEvents;
use Crystal\Finance\Core\Exception\InvalidDomainEvent;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DomainEventTest extends TestCase
{
    public function testGenericEventCarriesMetadata(): void
    {
        $occurredAt = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $auditContext = AuditContextFactory::system('Posted by test');
        $event = GenericDomainEvent::record(
            'Ledger.TransactionPosted',
            'ltx_123',
            $occurredAt,
            EventMetadata::fromArray(['balanced' => true]),
            $auditContext,
            eventId: EventId::fromString('evt_123'),
        );

        self::assertSame('evt_123', $event->eventId()->value());
        self::assertSame('Ledger.TransactionPosted', $event->eventName());
        self::assertSame('ltx_123', $event->aggregateId());
        self::assertSame($occurredAt, $event->occurredAt());
        self::assertSame(['balanced' => true], $event->metadata()->toArray());
        self::assertSame($auditContext, $event->auditContext());
    }

    public function testShortAggregateAndEventIdsAreAllowed(): void
    {
        $event = GenericDomainEvent::record(
            'Ledger.TransactionPosted',
            '1',
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            eventId: EventId::fromString('1'),
        );

        self::assertSame('1', $event->eventId()->value());
        self::assertSame('1', $event->aggregateId());
    }

    public function testEmptyAggregateIdIsRejected(): void
    {
        $this->expectException(InvalidDomainEvent::class);

        GenericDomainEvent::record(
            'Ledger.TransactionPosted',
            '',
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );
    }

    public function testInvalidEventNameIsRejected(): void
    {
        $this->expectException(InvalidDomainEvent::class);

        GenericDomainEvent::record(
            'ledger.transaction_posted',
            'ltx_123',
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );
    }

    public function testInvalidEventIdentifierIsRejected(): void
    {
        $this->expectException(InvalidDomainEvent::class);

        EventId::fromString('bad id');
    }

    public function testMetadataRejectsNonStringKey(): void
    {
        $this->expectException(InvalidDomainEvent::class);

        $this->metadataFromUncheckedArray([0 => 'import']);
    }

    public function testMetadataRejectsUnsupportedValue(): void
    {
        $this->expectException(InvalidDomainEvent::class);

        $this->metadataFromUncheckedArray(['details' => ['nested' => true]]);
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

    /**
     * @param array<array-key, mixed> $metadata
     */
    private function metadataFromUncheckedArray(array $metadata): void
    {
        (new \ReflectionMethod(EventMetadata::class, 'fromArray'))->invoke(null, $metadata);
    }
}

final class AuditContextFactory
{
    public static function system(string $reason): \Crystal\Finance\Core\Audit\AuditContext
    {
        return \Crystal\Finance\Core\Audit\AuditContext::record(
            \Crystal\Finance\Core\Audit\Actor::system(),
            $reason,
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );
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
