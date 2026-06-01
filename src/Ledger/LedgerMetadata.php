<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

/**
 * @phpstan-type MetadataValue string|int|bool|null
 */
final readonly class LedgerMetadata
{
    /**
     * @param array<string, string|int|bool|null> $values
     */
    private function __construct(private array $values)
    {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param array<string, string|int|bool|null> $values
     */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    #[\NoDiscard]
    public function with(string $key, string|int|bool|null $value): self
    {
        $values = $this->values;
        $values[$key] = $value;

        return new self($values);
    }

    public function get(string $key): string|int|bool|null
    {
        return $this->values[$key] ?? null;
    }

    /**
     * @return array<string, string|int|bool|null>
     */
    public function toArray(): array
    {
        return $this->values;
    }
}
