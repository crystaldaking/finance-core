<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Invariants;

use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Percentage;
use Crystal\Finance\Core\Money\RoundingMode;
use PHPUnit\Framework\TestCase;

final class MoneyInvariantTest extends TestCase
{
    public function testAdditionSubtractionAndNegationPreserveIdentityAcrossAssetScales(): void
    {
        foreach ($this->moneyPairs() as [$assetId, $leftAmount, $rightAmount]) {
            $left = Money::of($leftAmount, $assetId, $this->registry());
            $right = Money::of($rightAmount, $assetId, $this->registry());

            self::assertSame($left->toDecimalString(), $left->plus($right)->minus($right)->toDecimalString());
            self::assertSame($left->asset()->id()->value(), $left->plus($right)->asset()->id()->value());
            self::assertTrue($left->plus($left->negated())->isZero());
            self::assertSame($left->toDecimalString(), $left->negated()->negated()->toDecimalString());
        }
    }

    public function testMinorUnitRoundTripsDoNotRequireNativeIntegerRange(): void
    {
        $cases = [
            ['EUR', '123456789012345678901234567890', '1234567890123456789012345678.90'],
            ['USDT@TRON', '1000000000000000000000001', '1000000000000000000.000001'],
            ['ETH@ETHEREUM', '1123456789123456789123456789', '1123456789.123456789123456789'],
            ['ETH@ETHEREUM', '-1', '-0.000000000000000001'],
        ];

        foreach ($cases as [$assetId, $minorUnits, $decimal]) {
            $money = Money::ofMinor($minorUnits, $assetId, $this->registry());

            self::assertSame($minorUnits, $money->toMinorUnitString());
            self::assertSame($decimal, $money->toDecimalString());
        }
    }

    public function testAllocationAlwaysPreservesTotalMinorUnitsAndAssetIdentity(): void
    {
        $cases = [
            ['EUR', '10.00', [1, 1, 1]],
            ['EUR', '-10.00', [1, 2, 7]],
            ['USDT@TRON', '0.000007', [2, 3]],
            ['ETH@ETHEREUM', '0.000000000000000007', [1, 1, 1]],
        ];

        foreach ($cases as [$assetId, $amount, $ratios]) {
            $total = Money::of($amount, $assetId, $this->registry());
            $allocatedTotal = Money::zero($assetId, $this->registry());

            foreach ($total->allocate($ratios) as $part) {
                self::assertSame($assetId, $part->asset()->id()->value());
                $allocatedTotal = $allocatedTotal->plus($part);
            }

            self::assertSame($total->toMinorUnitString(), $allocatedTotal->toMinorUnitString());
            self::assertSame($total->toDecimalString(), $allocatedTotal->toDecimalString());
        }
    }

    public function testMultiplicationAndPercentageAlwaysReturnTheAssetScale(): void
    {
        $cases = [
            ['EUR', '10.00', '1.234', '12.34', '2.555', '0.26'],
            ['USDT@TRON', '10.000000', '1.234567', '12.345670', '2.555', '0.255500'],
            ['ETH@ETHEREUM', '1.000000000000000001', '2', '2.000000000000000002', '0.000000000000000001', '0.000000000000000000'],
        ];

        foreach ($cases as [$assetId, $amount, $multiplier, $multiplied, $percentage, $percentageAmount]) {
            $money = Money::of($amount, $assetId, $this->registry());

            self::assertSame(
                $multiplied,
                $money->multipliedBy($multiplier, RoundingMode::HalfUp)->toDecimalString(),
            );
            self::assertSame(
                $percentageAmount,
                $money->percentage(Percentage::of($percentage), RoundingMode::HalfUp)->toDecimalString(),
            );
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function moneyPairs(): array
    {
        return [
            ['EUR', '0.01', '0.02'],
            ['USD', '123456789012345.67', '0.33'],
            ['USDT@TRON', '100.123456', '0.000001'],
            ['ETH@ETHEREUM', '1.123456789123456789', '0.000000000000000001'],
        ];
    }

    private function registry(): AssetRegistry
    {
        return AssetRegistry::default();
    }
}
