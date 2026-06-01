<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Fee;

final readonly class FeeContext
{
    /**
     * @param array<string, string|int|bool|null> $metadata
     */
    private function __construct(
        private string $operation,
        private array $metadata,
    ) {
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
}
