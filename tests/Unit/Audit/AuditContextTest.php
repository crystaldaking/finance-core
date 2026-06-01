<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Audit;

use Crystal\Finance\Core\Audit\Actor;
use Crystal\Finance\Core\Audit\ActorType;
use Crystal\Finance\Core\Audit\AuditContext;
use Crystal\Finance\Core\Exception\InvalidAuditContext;
use Crystal\Finance\Core\Tests\Fixtures\FixedClock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AuditContextTest extends TestCase
{
    public function testCreatesAuditContextFromExplicitTimestamp(): void
    {
        $occurredAt = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $context = AuditContext::record(
            Actor::of(ActorType::Agent, 'agent:codex'),
            'Created ledger transaction',
            $occurredAt,
            metadata: ['source' => 'test'],
        );

        self::assertSame(ActorType::Agent, $context->actor()->type());
        self::assertSame('agent:codex', $context->actor()->id());
        self::assertSame('Created ledger transaction', $context->reason());
        self::assertSame($occurredAt, $context->occurredAt());
        self::assertSame(['source' => 'test'], $context->metadata());
    }

    public function testCreatesAuditContextFromClock(): void
    {
        $clock = new FixedClock(new DateTimeImmutable('2026-01-01T12:00:00+00:00'));

        $context = AuditContext::fromClock(Actor::system(), 'System reconciliation', $clock);

        self::assertSame('2026-01-01T12:00:00+00:00', $context->occurredAt()->format(DATE_ATOM));
    }

    public function testRejectsEmptyReason(): void
    {
        $this->expectException(InvalidAuditContext::class);

        AuditContext::record(Actor::system(), '', new DateTimeImmutable('2026-01-01T00:00:00+00:00'));
    }
}
