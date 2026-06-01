<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Fee;

use Crystal\Finance\Core\Money\Money;

final readonly class FeeBreakdownLine
{
    public function __construct(
        private string $label,
        private FeeComponentType $type,
        private Money $amount,
    ) {
    }

    public static function component(string $label, FeeComponentType $type, Money $amount): self
    {
        return new self($label, $type, $amount);
    }

    public static function minimumAdjustment(Money $amount): self
    {
        return new self('minimum_fee_adjustment', FeeComponentType::Minimum, $amount);
    }

    public static function maximumAdjustment(Money $amount): self
    {
        return new self('maximum_fee_adjustment', FeeComponentType::Maximum, $amount);
    }

    public function label(): string
    {
        return $this->label;
    }

    public function type(): FeeComponentType
    {
        return $this->type;
    }

    public function amount(): Money
    {
        return $this->amount;
    }
}
