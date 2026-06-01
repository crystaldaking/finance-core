<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidAllocation extends DomainException
{
    public static function emptyRatios(): self
    {
        return new self('Allocation requires at least one ratio.');
    }

    public static function invalidRatio(string $ratio): self
    {
        return new self(sprintf('Invalid allocation ratio "%s". Ratios must be positive integers.', $ratio));
    }
}
