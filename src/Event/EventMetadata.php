<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Event;

final readonly class EventMetadata
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

    /**
     * @return array<string, string|int|bool|null>
     */
    public function toArray(): array
    {
        return $this->values;
    }
}
