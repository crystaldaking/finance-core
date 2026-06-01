<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Idempotency;

use Crystal\Finance\Core\Exception\InvalidIdempotencyScope;

final readonly class IdempotencyScope
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (preg_match('/^[a-z0-9][a-z0-9_-]*(?::[a-z0-9][a-z0-9_-]*)*$/', $value) !== 1) {
            throw InvalidIdempotencyScope::fromString($value);
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
