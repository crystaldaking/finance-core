<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Idempotency;

use Crystal\Finance\Core\Exception\InvalidIdempotencyKey;

final readonly class IdempotencyKey
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{7,127}$/', $value) !== 1) {
            throw InvalidIdempotencyKey::fromString($value);
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
