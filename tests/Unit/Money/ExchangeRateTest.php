<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Money;

use Crystal\Finance\Core\Exception\AssetMismatch;
use Crystal\Finance\Core\Exception\InvalidRate;
use Crystal\Finance\Core\Money\AssetPair;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\ExchangeRate;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Rate;
use Crystal\Finance\Core\Money\RoundingMode;
use PHPUnit\Framework\TestCase;

final class ExchangeRateTest extends TestCase
{
    public function testConvertsBaseMoneyToQuoteMoneyWithExplicitRounding(): void
    {
        $registry = AssetRegistry::default();
        $pair = AssetPair::of($registry->get('EUR'), $registry->get('USD'));
        $rate = Rate::of('1.0875');
        $exchangeRate = ExchangeRate::of($pair, $rate);

        $converted = $exchangeRate->convert(Money::of('10.00', 'EUR', $registry), RoundingMode::HalfUp);

        self::assertSame($pair, $exchangeRate->pair());
        self::assertSame($rate, $exchangeRate->rate());
        self::assertSame('1.0875', $rate->toDecimalString());
        self::assertSame('10.88', $converted->toDecimalString());
        self::assertSame('USD', $converted->asset()->id()->value());
    }

    public function testAssetPairRejectsSameAsset(): void
    {
        $eur = AssetRegistry::default()->get('EUR');

        $this->expectException(AssetMismatch::class);

        AssetPair::of($eur, $eur);
    }

    public function testConversionRejectsMoneyThatDoesNotMatchBaseAsset(): void
    {
        $registry = AssetRegistry::default();
        $exchangeRate = ExchangeRate::of(
            AssetPair::of($registry->get('EUR'), $registry->get('USD')),
            Rate::of('1.10'),
        );

        $this->expectException(AssetMismatch::class);

        $exchangeRate->convert(Money::of('10.00', 'USD', $registry), RoundingMode::HalfUp);
    }

    public function testRateMustBePositive(): void
    {
        $this->expectException(InvalidRate::class);

        Rate::of('0');
    }

    public function testRateMustBePlainDecimalString(): void
    {
        $this->expectException(InvalidRate::class);

        Rate::of('1e3');
    }
}
