<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidRate extends DomainException
{
    public static function fromString(string $rate): self
    {
        return new self(sprintf('Invalid rate "%s". Use a positive plain decimal string.', $rate));
    }
}
