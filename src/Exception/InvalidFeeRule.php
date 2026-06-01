<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidFeeRule extends DomainException
{
    public static function negativeComponent(string $label): self
    {
        return new self(sprintf('Fee component "%s" cannot be negative.', $label));
    }

    public static function maxBelowMin(): self
    {
        return new self('Fee maximum cannot be lower than fee minimum.');
    }

    public static function netWouldBecomeNegative(string $gross, string $fee): self
    {
        return new self(sprintf('Fee "%s" cannot exceed gross amount "%s" unless negative net is explicitly allowed.', $fee, $gross));
    }
}
