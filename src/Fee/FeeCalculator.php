<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Fee;

use Crystal\Finance\Core\Exception\InvalidFeeRule;
use Crystal\Finance\Core\Money\Money;

final readonly class FeeCalculator
{
    public function calculate(Money $amount, FeeRule $rule, FeeContext $context): FeeResult
    {
        $totalFee = Money::zero($amount->asset());
        $lines = [];
        $minimum = null;
        $maximum = null;

        foreach ($rule->components() as $component) {
            match ($component->type()) {
                FeeComponentType::Percentage => $this->applyPercentage($amount, $component, $totalFee, $lines),
                FeeComponentType::Fixed => $this->applyFixed($amount, $component, $totalFee, $lines),
                FeeComponentType::Minimum => $minimum = $this->validatedAmount($amount, $component),
                FeeComponentType::Maximum => $maximum = $this->validatedAmount($amount, $component),
            };
        }

        if ($minimum !== null && $maximum !== null && $maximum->compareTo($minimum) < 0) {
            throw InvalidFeeRule::maxBelowMin();
        }

        if ($minimum !== null && $totalFee->compareTo($minimum) < 0) {
            $adjustment = $minimum->minus($totalFee);
            $totalFee = $minimum;
            $lines[] = FeeBreakdownLine::minimumAdjustment($adjustment);
        }

        if ($maximum !== null && $totalFee->compareTo($maximum) > 0) {
            $adjustment = $maximum->minus($totalFee);
            $totalFee = $maximum;
            $lines[] = FeeBreakdownLine::maximumAdjustment($adjustment);
        }

        if (!$rule->negativeNetAllowed() && $totalFee->compareTo($amount) > 0) {
            throw InvalidFeeRule::netWouldBecomeNegative($amount->toDecimalString(), $totalFee->toDecimalString());
        }

        return FeeResult::of(
            $amount,
            $totalFee,
            FeeBreakdown::of(...$lines),
            $context,
        );
    }

    /**
     * @param list<FeeBreakdownLine> $lines
     */
    private function applyPercentage(Money $amount, FeeComponent $component, Money &$totalFee, array &$lines): void
    {
        $percentage = $component->percentageValue();

        if ($percentage === null) {
            throw InvalidFeeRule::negativeComponent($component->label());
        }

        $fee = $amount->percentage($percentage, $component->roundingMode());
        $totalFee = $totalFee->plus($fee);
        $lines[] = FeeBreakdownLine::component($component->label(), $component->type(), $fee);
    }

    /**
     * @param list<FeeBreakdownLine> $lines
     */
    private function applyFixed(Money $amount, FeeComponent $component, Money &$totalFee, array &$lines): void
    {
        $fee = $this->validatedAmount($amount, $component);
        $totalFee = $totalFee->plus($fee);
        $lines[] = FeeBreakdownLine::component($component->label(), $component->type(), $fee);
    }

    private function validatedAmount(Money $amount, FeeComponent $component): Money
    {
        $componentAmount = $component->amountValue();

        if ($componentAmount === null) {
            throw InvalidFeeRule::negativeComponent($component->label());
        }

        if (!$componentAmount->asset()->isCompatibleWith($amount->asset())) {
            throw InvalidFeeRule::componentAssetMismatch(
                $component->label(),
                $amount->asset()->id()->value(),
                $componentAmount->asset()->id()->value(),
            );
        }

        return $componentAmount;
    }
}
