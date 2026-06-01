<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\RoundingNecessaryException;
use Crystal\Finance\Core\Exception\AssetMismatch;
use Crystal\Finance\Core\Exception\InvalidAllocation;
use Crystal\Finance\Core\Exception\InvalidMoneyAmount;

final readonly class Money
{
    private function __construct(
        private BigDecimal $amount,
        private Asset $asset,
    ) {
    }

    public static function of(string $amount, Asset|string $asset, ?AssetRegistry $registry = null): self
    {
        $resolvedAsset = self::resolveAsset($asset, $registry);

        try {
            $scaled = DecimalString::money($amount)->toScale($resolvedAsset->scale());
        } catch (RoundingNecessaryException) {
            throw InvalidMoneyAmount::scaleExceeded($amount, $resolvedAsset->scale());
        }

        return new self($scaled, $resolvedAsset);
    }

    public static function ofMinor(BigInteger|int|string $minorUnits, Asset|string $asset, ?AssetRegistry $registry = null): self
    {
        $minorUnitsString = $minorUnits instanceof BigInteger ? $minorUnits->toString() : (string) $minorUnits;

        if (preg_match('/^-?(?:0|[1-9][0-9]*)$/', $minorUnitsString) !== 1) {
            throw InvalidMoneyAmount::invalidMinorUnits($minorUnitsString);
        }

        $resolvedAsset = self::resolveAsset($asset, $registry);

        try {
            $amount = BigDecimal::ofUnscaledValue($minorUnitsString, $resolvedAsset->scale())
                ->toScale($resolvedAsset->scale());
        } catch (MathException) {
            throw InvalidMoneyAmount::invalidMinorUnits($minorUnitsString);
        }

        return new self($amount, $resolvedAsset);
    }

    public static function zero(Asset|string $asset, ?AssetRegistry $registry = null): self
    {
        $resolvedAsset = self::resolveAsset($asset, $registry);

        return new self(BigDecimal::zero()->toScale($resolvedAsset->scale()), $resolvedAsset);
    }

    public static function fromDecimal(BigDecimal $amount, Asset $asset, RoundingMode $roundingMode): self
    {
        return new self($amount->toScale($asset->scale(), $roundingMode->toBrick()), $asset);
    }

    public function amount(): BigDecimal
    {
        return $this->amount;
    }

    public function asset(): Asset
    {
        return $this->asset;
    }

    public function plus(self $other): self
    {
        $this->assertSameAsset($other);

        return new self($this->amount->plus($other->amount)->toScale($this->asset->scale()), $this->asset);
    }

    public function minus(self $other): self
    {
        $this->assertSameAsset($other);

        return new self($this->amount->minus($other->amount)->toScale($this->asset->scale()), $this->asset);
    }

    public function negated(): self
    {
        return new self($this->amount->negated()->toScale($this->asset->scale()), $this->asset);
    }

    public function absolute(): self
    {
        return $this->isNegative() ? $this->negated() : $this;
    }

    public function multipliedBy(string $multiplier, RoundingMode $roundingMode): self
    {
        return self::fromDecimal($this->amount->multipliedBy(DecimalString::money($multiplier)), $this->asset, $roundingMode);
    }

    public function percentage(Percentage $percentage, RoundingMode $roundingMode): self
    {
        return self::fromDecimal($this->amount->multipliedBy($percentage->toMultiplier()), $this->asset, $roundingMode);
    }

    public function compareTo(self $other): int
    {
        $this->assertSameAsset($other);

        return $this->amount->compareTo($other->amount);
    }

    public function isZero(): bool
    {
        return $this->amount->isZero();
    }

    public function isPositive(): bool
    {
        return $this->amount->isPositive();
    }

    public function isNegative(): bool
    {
        return $this->amount->isNegative();
    }

    public function isSameAsset(self $other): bool
    {
        return $this->asset->equals($other->asset);
    }

    public function toDecimalString(): string
    {
        return $this->amount->toString();
    }

    public function toMinorUnitString(): string
    {
        return $this->amount->getUnscaledValue()->toString();
    }

    /**
     * @param list<int|string|BigInteger> $ratios
     * @return list<self>
     */
    public function allocate(array $ratios): array
    {
        if ($ratios === []) {
            throw InvalidAllocation::emptyRatios();
        }

        $parsedRatios = [];
        $ratioSum = BigInteger::zero();

        foreach ($ratios as $ratio) {
            $ratioString = $ratio instanceof BigInteger ? $ratio->toString() : (string) $ratio;

            if (preg_match('/^(?:[1-9][0-9]*)$/', $ratioString) !== 1) {
                throw InvalidAllocation::invalidRatio($ratioString);
            }

            $parsedRatio = BigInteger::of($ratioString);
            $parsedRatios[] = $parsedRatio;
            $ratioSum = $ratioSum->plus($parsedRatio);
        }

        $negative = $this->amount->isNegative();
        $minorTotal = $this->amount->getUnscaledValue()->abs();
        $allocated = [];
        $allocatedTotal = BigInteger::zero();

        foreach ($parsedRatios as $ratio) {
            $minorPart = $minorTotal->multipliedBy($ratio)->quotient($ratioSum);
            $allocated[] = $minorPart;
            $allocatedTotal = $allocatedTotal->plus($minorPart);
        }

        $residual = $minorTotal->minus($allocatedTotal)->toInt();

        for ($index = 0; $index < $residual; $index++) {
            $allocated[$index] = $allocated[$index]->plus(1);
        }

        $result = [];

        foreach ($allocated as $minorPart) {
            $signedMinorPart = $negative ? $minorPart->negated() : $minorPart;
            $result[] = self::ofMinor($signedMinorPart, $this->asset);
        }

        return $result;
    }

    private function assertSameAsset(self $other): void
    {
        if (!$this->asset->equals($other->asset)) {
            throw AssetMismatch::between($this->asset->id()->value(), $other->asset->id()->value());
        }
    }

    private static function resolveAsset(Asset|string $asset, ?AssetRegistry $registry): Asset
    {
        if ($asset instanceof Asset) {
            return $asset;
        }

        return ($registry ?? AssetRegistry::default())->get($asset);
    }
}
