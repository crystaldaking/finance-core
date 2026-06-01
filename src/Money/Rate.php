<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Money;

use Brick\Math\BigDecimal;
use Crystal\Finance\Core\Exception\InvalidRate;

final readonly class Rate
{
    private function __construct(private BigDecimal $value)
    {
        if (!$value->isPositive()) {
            throw InvalidRate::fromString($value->toString());
        }
    }

    public static function of(string $value): self
    {
        return new self(DecimalString::rate($value));
    }

    public function value(): BigDecimal
    {
        return $this->value;
    }

    public function toDecimalString(): string
    {
        return $this->value->toString();
    }
}
