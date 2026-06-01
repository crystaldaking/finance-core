<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidIdempotencyKey extends DomainException
{
    public static function fromString(string $key): self
    {
        return new self(sprintf('Invalid idempotency key "%s".', $key));
    }
}
