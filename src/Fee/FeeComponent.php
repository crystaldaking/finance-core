<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Fee;

use Crystal\Finance\Core\Exception\InvalidFeeRule;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Percentage;
use Crystal\Finance\Core\Money\RoundingMode;

final readonly class FeeComponent
{
    private function __construct(
        private FeeComponentType $type,
        private string $label,
        private ?Percentage $percentage,
        private ?Money $amount,
        private RoundingMode $roundingMode,
    ) {
        $percentage?->assertNonNegative();

        if ($amount !== null && $amount->isNegative()) {
            throw InvalidFeeRule::negativeComponent($label);
        }
    }

    public static function percentage(Percentage $percentage, string $label, RoundingMode $roundingMode): self
    {
        return new self(FeeComponentType::Percentage, $label, $percentage, null, $roundingMode);
    }

    public static function fixed(Money $amount, string $label): self
    {
        return new self(FeeComponentType::Fixed, $label, null, $amount, RoundingMode::Unnecessary);
    }

    public static function minimum(Money $amount, string $label): self
    {
        return new self(FeeComponentType::Minimum, $label, null, $amount, RoundingMode::Unnecessary);
    }

    public static function maximum(Money $amount, string $label): self
    {
        return new self(FeeComponentType::Maximum, $label, null, $amount, RoundingMode::Unnecessary);
    }

    public function type(): FeeComponentType
    {
        return $this->type;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function percentageValue(): ?Percentage
    {
        return $this->percentage;
    }

    public function amountValue(): ?Money
    {
        return $this->amount;
    }

    public function roundingMode(): RoundingMode
    {
        return $this->roundingMode;
    }
}
