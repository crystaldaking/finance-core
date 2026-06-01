<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Event;

use Crystal\Finance\Core\Exception\InvalidDomainEvent;

final readonly class EventMetadata
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
                throw InvalidDomainEvent::invalidMetadataKey($key);
            }

            if (!is_string($value) && !is_int($value) && !is_bool($value) && $value !== null) {
                throw InvalidDomainEvent::invalidMetadataValue($key);
            }
        }
    }
}
