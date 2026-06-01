<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Money;

use Crystal\Finance\Core\Exception\AssetMismatch;
use Crystal\Finance\Core\Exception\InvalidAllocation;
use Crystal\Finance\Core\Exception\InvalidMoneyAmount;
use Crystal\Finance\Core\Exception\InvalidPercentage;
use Crystal\Finance\Core\Money\Asset;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Percentage;
use Crystal\Finance\Core\Money\RoundingMode;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    private function registry(): AssetRegistry
    {
        return AssetRegistry::default();
    }

    public function testCreatesFiatMoney(): void
    {
        $money = Money::of('100.25', 'EUR', $this->registry());

        self::assertSame('100.25', $money->toDecimalString());
        self::assertSame('10025', $money->toMinorUnitString());
        self::assertSame('EUR', $money->asset()->id()->value());
    }

    public function testCreatesCryptoMoneyWithConfiguredScale(): void
    {
        $usdt = Money::of('100.123456', 'USDT@TRON', $this->registry());
        $btc = Money::of('0.12345678', 'BTC@BITCOIN', $this->registry());
        $eth = Money::of('1.123456789123456789', 'ETH@ETHEREUM', $this->registry());

        self::assertSame('100.123456', $usdt->toDecimalString());
        self::assertSame('0.12345678', $btc->toDecimalString());
        self::assertSame('1.123456789123456789', $eth->toDecimalString());
        self::assertSame('1123456789123456789', $eth->toMinorUnitString());
    }

    public function testCreatesMoneyFromMinorUnitsWithoutIntegerOverflow(): void
    {
        $money = Money::ofMinor('1123456789123456789', 'ETH@ETHEREUM', $this->registry());

        self::assertSame('1.123456789123456789', $money->toDecimalString());
        self::assertSame('1.123456789123456789', $money->amount()->toString());
    }

    public function testRejectsInvalidMinorUnitString(): void
    {
        $this->expectException(InvalidMoneyAmount::class);

        Money::ofMinor('001', 'EUR', $this->registry());
    }

    public function testRejectsInvalidDecimalString(): void
    {
        $this->expectException(InvalidMoneyAmount::class);

        Money::of('1e3', 'EUR', $this->registry());
    }

    public function testRejectsTooManyDecimalPlacesWithoutExplicitRounding(): void
    {
        $this->expectException(InvalidMoneyAmount::class);

        Money::of('10.001', 'EUR', $this->registry());
    }

    public function testAddsAndSubtractsSameAssetMoney(): void
    {
        $first = Money::of('10.25', 'EUR', $this->registry());
        $second = Money::of('2.15', 'EUR', $this->registry());

        self::assertSame('12.40', $first->plus($second)->toDecimalString());
        self::assertSame('8.10', $first->minus($second)->toDecimalString());
    }

    public function testRejectsOperationsAcrossDifferentAssets(): void
    {
        $this->expectException(AssetMismatch::class);

        $unused = Money::of('10.00', 'USDT@TRON', $this->registry())
            ->plus(Money::of('10.00', 'USDT@ETHEREUM', $this->registry()));
    }

    public function testRejectsOperationsAcrossSameAssetIdWithDifferentScale(): void
    {
        $this->expectException(AssetMismatch::class);

        $unused = Money::of('1.23', Asset::fiat('EUR', 2))
            ->plus(Money::of('1.230', Asset::fiat('EUR', 3)));
    }

    public function testChecksAssetIdentityExplicitly(): void
    {
        self::assertTrue(Money::of('1.00', 'EUR', $this->registry())->isSameAsset(
            Money::of('2.00', 'EUR', $this->registry()),
        ));
        self::assertFalse(Money::of('1.00', 'USDT@TRON', $this->registry())->isSameAsset(
            Money::of('1.00', 'USDT@ETHEREUM', $this->registry()),
        ));
    }

    public function testChecksAssetCompatibilityExplicitly(): void
    {
        self::assertFalse(Money::of('1.23', Asset::fiat('EUR', 2))->isCompatibleWith(
            Money::of('1.230', Asset::fiat('EUR', 3)),
        ));
    }

    public function testMultipliesWithExplicitRounding(): void
    {
        $result = Money::of('10.00', 'EUR', $this->registry())
            ->multipliedBy('1.234', RoundingMode::HalfUp);

        self::assertSame('12.34', $result->toDecimalString());
    }

    public function testCalculatesPercentageWithExplicitRounding(): void
    {
        $fee = Money::of('100.00', 'EUR', $this->registry())
            ->percentage(Percentage::of('2.555'), RoundingMode::HalfUp);

        self::assertSame('2.56', $fee->toDecimalString());
    }

    public function testRejectsNegativePercentage(): void
    {
        $this->expectException(InvalidPercentage::class);

        Percentage::of('-1');
    }

    public function testComparisonAndSigns(): void
    {
        $positive = Money::of('1.00', 'EUR', $this->registry());
        $negative = Money::of('-1.00', 'EUR', $this->registry());
        $zero = Money::zero('EUR', $this->registry());

        self::assertTrue($positive->isPositive());
        self::assertTrue($negative->isNegative());
        self::assertTrue($zero->isZero());
        self::assertSame(1, $positive->compareTo($zero));
        self::assertSame('1.00', $negative->absolute()->toDecimalString());
    }

    public function testAllocationPreservesTotalAndDistributesResidualDeterministically(): void
    {
        $parts = Money::of('10.00', 'EUR', $this->registry())->allocate([1, 1, 1]);

        self::assertCount(3, $parts);
        self::assertSame('3.34', $parts[0]->toDecimalString());
        self::assertSame('3.33', $parts[1]->toDecimalString());
        self::assertSame('3.33', $parts[2]->toDecimalString());
        self::assertSame(
            '10.00',
            $parts[0]->plus($parts[1])->plus($parts[2])->toDecimalString(),
        );
    }

    public function testAllocationRejectsInvalidRatios(): void
    {
        $this->expectException(InvalidAllocation::class);

        $unused = Money::of('10.00', 'EUR', $this->registry())->allocate([1, 0]);
    }

    public function testAllocationRejectsEmptyRatios(): void
    {
        $this->expectException(InvalidAllocation::class);

        $unused = Money::of('10.00', 'EUR', $this->registry())->allocate([]);
    }
}
