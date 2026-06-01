<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit;

use Crystal\Finance\Core\CoreVersion;
use PHPUnit\Framework\TestCase;

final class CoreVersionTest extends TestCase
{
    public function testVersionConstantExists(): void
    {
        self::assertSame('0.2.0', CoreVersion::VERSION);
    }
}
