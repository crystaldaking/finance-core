<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidIdempotencyTtl extends DomainException
{
    public static function nonPositive(): self
    {
        return new self('Idempotency TTL must resolve to a positive duration.');
    }
}
