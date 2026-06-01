<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Fee;

use Crystal\Finance\Core\Exception\InvalidFeeResult;
use Crystal\Finance\Core\Money\Money;

final readonly class FeeResult
{
    private Money $gross;

    private Money $totalFee;

    private Money $net;

    private FeeBreakdown $breakdown;

    private FeeContext $context;

    public function __construct(Money $gross, Money $totalFee, Money $net, FeeBreakdown $breakdown, FeeContext $context)
    {
        $expectedNet = $gross->minus($totalFee);

        if ($expectedNet->compareTo($net) !== 0) {
            throw InvalidFeeResult::netMismatch($expectedNet->toDecimalString(), $net->toDecimalString());
        }

        $this->gross = $gross;
        $this->totalFee = $totalFee;
        $this->net = $net;
        $this->assertBreakdownMatchesTotalFee($breakdown, $totalFee);
        $this->breakdown = $breakdown;
        $this->context = $context;
    }

    public static function of(Money $gross, Money $totalFee, FeeBreakdown $breakdown, FeeContext $context): self
    {
        return new self($gross, $totalFee, $gross->minus($totalFee), $breakdown, $context);
    }

    public function gross(): Money
    {
        return $this->gross;
    }

    public function totalFee(): Money
    {
        return $this->totalFee;
    }

    public function net(): Money
    {
        return $this->net;
    }

    public function breakdown(): FeeBreakdown
    {
        return $this->breakdown;
    }

    public function context(): FeeContext
    {
        return $this->context;
    }

    private function assertBreakdownMatchesTotalFee(FeeBreakdown $breakdown, Money $totalFee): void
    {
        $lineTotal = Money::zero($totalFee->asset());

        foreach ($breakdown->lines() as $line) {
            if (!$line->amount()->asset()->isCompatibleWith($totalFee->asset())) {
                throw InvalidFeeResult::breakdownAssetMismatch(
                    $totalFee->asset()->id()->value(),
                    $line->amount()->asset()->id()->value(),
                );
            }

            $lineTotal = $lineTotal->plus($line->amount());
        }

        if ($lineTotal->compareTo($totalFee) !== 0) {
            throw InvalidFeeResult::breakdownTotalMismatch(
                $totalFee->toDecimalString(),
                $lineTotal->toDecimalString(),
            );
        }
    }
}
