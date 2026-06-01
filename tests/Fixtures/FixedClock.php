<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Fixtures;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final class FixedClock implements ClockInterface
{
    public function __construct(private DateTimeImmutable $now)
    {
    }

    #[\Override]
    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function moveTo(DateTimeImmutable $now): void
    {
        $this->now = $now;
    }
}
