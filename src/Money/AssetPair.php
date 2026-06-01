<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Money;

use Crystal\Finance\Core\Exception\InvalidAssetPair;

final readonly class AssetPair
{
    private function __construct(
        private Asset $base,
        private Asset $quote,
    ) {
        if ($base->equals($quote)) {
            throw InvalidAssetPair::sameBaseAndQuote($base->id()->value());
        }
    }

    public static function of(Asset $base, Asset $quote): self
    {
        return new self($base, $quote);
    }

    public function base(): Asset
    {
        return $this->base;
    }

    public function quote(): Asset
    {
        return $this->quote;
    }
}
