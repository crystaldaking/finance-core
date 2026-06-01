<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Fee;

use Crystal\Finance\Core\Money\Money;

final readonly class FeeResult
{
    public function __construct(
        private Money $gross,
        private Money $totalFee,
        private Money $net,
        private FeeBreakdown $breakdown,
        private FeeContext $context,
    ) {
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
}
