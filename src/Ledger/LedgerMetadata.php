<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

use Crystal\Finance\Core\Exception\InvalidLedgerTransaction;

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
        self::assertValidValues($values);
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

    /**
     * @param array<array-key, mixed> $values
     */
    private static function assertValidValues(array $values): void
    {
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                throw InvalidLedgerTransaction::invalidMetadataKey($key);
            }

            if (!is_string($value) && !is_int($value) && !is_bool($value) && $value !== null) {
                throw InvalidLedgerTransaction::invalidMetadataValue($key);
            }
        }
    }
}
