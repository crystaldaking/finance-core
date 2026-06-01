<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Money;

final readonly class ExchangeRate
{
    private function __construct(
        private AssetPair $pair,
        private Rate $rate,
    ) {
    }

    public static function of(AssetPair $pair, Rate $rate): self
    {
        return new self($pair, $rate);
    }

    public function pair(): AssetPair
    {
        return $this->pair;
    }

    public function rate(): Rate
    {
        return $this->rate;
    }

    public function convert(Money $money, RoundingMode $roundingMode): Money
    {
        if (!$money->asset()->equals($this->pair->base())) {
            throw \Crystal\Finance\Core\Exception\AssetMismatch::between(
                $money->asset()->id()->value(),
                $this->pair->base()->id()->value(),
            );
        }

        return Money::fromDecimal(
            $money->amount()->multipliedBy($this->rate->value()),
            $this->pair->quote(),
            $roundingMode,
        );
    }
}
