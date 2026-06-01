<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Money;

use Brick\Math\BigDecimal;
use Crystal\Finance\Core\Exception\InvalidPercentage;

final readonly class Percentage
{
    private function __construct(private BigDecimal $value)
    {
    }

    public static function of(string $value): self
    {
        return new self(DecimalString::percentage($value));
    }

    public function toDecimalString(): string
    {
        return $this->value->toString();
    }

    public function toMultiplier(): BigDecimal
    {
        $scale = $this->value->getScale() + 2;

        return $this->value->dividedBy('100', $scale);
    }

    public function assertNonNegative(): void
    {
        if ($this->value->isNegative()) {
            throw InvalidPercentage::fromString($this->value->toString());
        }
    }
}
