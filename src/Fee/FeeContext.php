<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Fee;

use Crystal\Finance\Core\Exception\InvalidFeeContext;

final readonly class FeeContext
{
    /**
     * @param array<string, string|int|bool|null> $metadata
     */
    private function __construct(
        private string $operation,
        private array $metadata,
    ) {
        self::assertValidMetadata($metadata);
    }

    /**
     * @param array<string, string|int|bool|null> $metadata
     */
    public static function make(string $operation, array $metadata = []): self
    {
        return new self($operation, $metadata);
    }

    public function operation(): string
    {
        return $this->operation;
    }

    /**
     * @return array<string, string|int|bool|null>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<array-key, mixed> $metadata
     */
    private static function assertValidMetadata(array $metadata): void
    {
        foreach ($metadata as $key => $value) {
            if (!is_string($key)) {
                throw InvalidFeeContext::invalidMetadataKey($key);
            }

            if (!is_string($value) && !is_int($value) && !is_bool($value) && $value !== null) {
                throw InvalidFeeContext::invalidMetadataValue($key);
            }
        }
    }
}
