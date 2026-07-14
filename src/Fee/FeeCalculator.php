<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Fee;

use Crystal\Finance\Core\Exception\InvalidFeeRule;
use Crystal\Finance\Core\Money\Money;

final readonly class FeeCalculator
{
    public function calculate(Money $amount, FeeRule $rule, FeeContext $context): FeeResult
    {
        if ($amount->isNegative()) {
            throw InvalidFeeRule::negativeGross($amount->toDecimalString());
        }

        $totalFee = Money::zero($amount->asset());
        $lines = [];
        $minimum = null;
        $maximum = null;

        foreach ($rule->components() as $component) {
            match ($component->type()) {
                FeeComponentType::Percentage => $this->applyPercentage($amount, $component, $totalFee, $lines),
                FeeComponentType::Fixed => $this->applyFixed($amount, $component, $totalFee, $lines),
                FeeComponentType::Minimum => $minimum = $component,
                FeeComponentType::Maximum => $maximum = $component,
            };
        }

        $minimumAmount = null;
        $minimumLabel = 'minimum_fee_adjustment';
        $maximumAmount = null;
        $maximumLabel = 'maximum_fee_adjustment';

        if ($minimum !== null) {
            $minimumAmount = $this->validatedAmount($amount, $minimum);
            $minimumLabel = $minimum->label() === 'minimum_fee' ? $minimumLabel : $minimum->label();
        }

        if ($maximum !== null) {
            $maximumAmount = $this->validatedAmount($amount, $maximum);
            $maximumLabel = $maximum->label() === 'maximum_fee' ? $maximumLabel : $maximum->label();
        }

        if ($minimumAmount !== null && $maximumAmount !== null && $maximumAmount->compareTo($minimumAmount) < 0) {
            throw InvalidFeeRule::maxBelowMin();
        }

        if ($minimumAmount !== null && $totalFee->compareTo($minimumAmount) < 0) {
            $adjustment = $minimumAmount->minus($totalFee);
            $totalFee = $minimumAmount;
            $lines[] = FeeBreakdownLine::minimumAdjustment($adjustment, $minimumLabel);
        }

        if ($maximumAmount !== null && $totalFee->compareTo($maximumAmount) > 0) {
            $adjustment = $maximumAmount->minus($totalFee);
            $totalFee = $maximumAmount;
            $lines[] = FeeBreakdownLine::maximumAdjustment($adjustment, $maximumLabel);
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
