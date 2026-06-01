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
