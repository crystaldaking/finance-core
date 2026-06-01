<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidMoneyAmount extends DomainException
{
    public static function fromString(string $amount): self
    {
        return new self(sprintf('Invalid money amount "%s". Use a plain decimal string.', $amount));
    }

    public static function scaleExceeded(string $amount, int $scale): self
    {
        return new self(sprintf('Money amount "%s" exceeds allowed scale %d.', $amount, $scale));
    }

    public static function invalidMinorUnits(string $minorUnits): self
    {
        return new self(sprintf('Invalid minor-unit amount "%s". Use an integer string.', $minorUnits));
    }
}
