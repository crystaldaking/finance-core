<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Fee;

use Crystal\Finance\Core\Exception\InvalidFeeContext;
use Crystal\Finance\Core\Fee\FeeContext;
use PHPUnit\Framework\TestCase;

final class FeeContextTest extends TestCase
{
    public function testAcceptsScalarMetadataValues(): void
    {
        $context = FeeContext::make('test_operation', [
            'provider' => 'internal',
            'attempt' => 1,
            'fallback' => false,
            'note' => null,
        ]);

        self::assertSame([
            'provider' => 'internal',
            'attempt' => 1,
            'fallback' => false,
            'note' => null,
        ], $context->metadata());
    }

    public function testRejectsNonStringMetadataKey(): void
    {
        $this->expectException(InvalidFeeContext::class);

        $this->makeContextWithUncheckedMetadata([0 => 'import']);
    }

    public function testRejectsUnsupportedMetadataValue(): void
    {
        $this->expectException(InvalidFeeContext::class);

        $this->makeContextWithUncheckedMetadata(['details' => ['nested' => true]]);
    }

    /**
     * @param array<array-key, mixed> $metadata
     */
    private function makeContextWithUncheckedMetadata(array $metadata): void
    {
        (new \ReflectionMethod(FeeContext::class, 'make'))->invoke(null, 'test_operation', $metadata);
    }
}
