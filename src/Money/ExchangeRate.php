<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Money;

use Crystal\Finance\Core\Exception\InvalidExchangeConversion;

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
        if (!$money->asset()->isCompatibleWith($this->pair->base())) {
            throw InvalidExchangeConversion::sourceAssetMismatch(
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
