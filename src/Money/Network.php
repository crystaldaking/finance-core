<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Money;

use Crystal\Finance\Core\Exception\InvalidNetwork;

final readonly class Network
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (preg_match('/^[A-Z0-9_-]{2,32}$/', $value) !== 1) {
            throw InvalidNetwork::fromString($value);
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->value;
    }
}
