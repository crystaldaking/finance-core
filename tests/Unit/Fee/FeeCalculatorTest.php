<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Fee;

use Crystal\Finance\Core\Exception\InvalidFeeRule;
use Crystal\Finance\Core\Fee\FeeCalculator;
use Crystal\Finance\Core\Fee\FeeComponentType;
use Crystal\Finance\Core\Fee\FeeContext;
use Crystal\Finance\Core\Fee\FeeRule;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Percentage;
use Crystal\Finance\Core\Money\RoundingMode;
use PHPUnit\Framework\TestCase;

final class FeeCalculatorTest extends TestCase
{
    public function testPercentageFee(): void
    {
        $result = $this->calculate(
            $this->eur('100.00'),
            FeeRule::make()->percent(Percentage::of('2.5'), 'service_fee'),
        );

        self::assertSame('2.50', $result->totalFee()->toDecimalString());
        self::assertSame('97.50', $result->net()->toDecimalString());
        self::assertSame('service_fee', $result->breakdown()->lines()[0]->label());
        self::assertSame(FeeComponentType::Percentage, $result->breakdown()->lines()[0]->type());
        self::assertSame('test_operation', $result->context()->operation());
        self::assertSame(['test' => true], $result->context()->metadata());
    }

    public function testFixedFee(): void
    {
        $result = $this->calculate(
            $this->eur('100.00'),
            FeeRule::make()->fixed($this->eur('0.30'), 'fixed_charge'),
        );

        self::assertSame('0.30', $result->totalFee()->toDecimalString());
        self::assertSame('99.70', $result->net()->toDecimalString());
    }

    public function testPercentageAndFixedFees(): void
    {
        $result = $this->calculate(
            $this->eur('100.00'),
            FeeRule::make()
                ->percent(Percentage::of('2.5'), 'percentage_charge')
                ->fixed($this->eur('0.30'), 'fixed_charge'),
        );

        self::assertSame('2.80', $result->totalFee()->toDecimalString());
        self::assertSame('97.20', $result->net()->toDecimalString());
        self::assertCount(2, $result->breakdown()->lines());
    }

    public function testMinimumFeeAddsAdjustmentLine(): void
    {
        $result = $this->calculate(
            $this->eur('10.00'),
            FeeRule::make()
                ->percent(Percentage::of('1'), 'percentage_charge')
                ->min($this->eur('1.00')),
        );

        self::assertSame('1.00', $result->totalFee()->toDecimalString());
        self::assertSame('0.90', $result->breakdown()->lines()[1]->amount()->toDecimalString());
        self::assertSame('minimum_fee_adjustment', $result->breakdown()->lines()[1]->label());
    }

    public function testMaximumFeeAddsNegativeAdjustmentLine(): void
    {
        $result = $this->calculate(
            $this->eur('1000.00'),
            FeeRule::make()
                ->percent(Percentage::of('10'), 'percentage_charge')
                ->max($this->eur('50.00')),
        );

        self::assertSame('50.00', $result->totalFee()->toDecimalString());
        self::assertSame('-50.00', $result->breakdown()->lines()[1]->amount()->toDecimalString());
        self::assertSame('950.00', $result->net()->toDecimalString());
    }

    public function testCryptoFee(): void
    {
        $registry = AssetRegistry::default();

        $result = $this->calculate(
            Money::of('100.000000', 'USDT@TRON', $registry),
            FeeRule::make()->percent(Percentage::of('1.25'), 'charge'),
        );

        self::assertSame('1.250000', $result->totalFee()->toDecimalString());
        self::assertSame('98.750000', $result->net()->toDecimalString());
    }

    public function testCurrencyMismatchIsRejected(): void
    {
        $this->expectException(InvalidFeeRule::class);

        $this->calculate(
            $this->eur('100.00'),
            FeeRule::make()->fixed(Money::of('1.00', 'USD', AssetRegistry::default())),
        );
    }

    public function testNetCannotBecomeNegativeByDefault(): void
    {
        $this->expectException(InvalidFeeRule::class);

        $this->calculate(
            $this->eur('1.00'),
            FeeRule::make()->fixed($this->eur('2.00')),
        );
    }

    public function testNegativeNetCanBeExplicitlyAllowed(): void
    {
        $result = $this->calculate(
            $this->eur('1.00'),
            FeeRule::make()->fixed($this->eur('2.00'))->allowNegativeNet(),
        );

        self::assertSame('-1.00', $result->net()->toDecimalString());
    }

    public function testRoundingBehaviorIsExplicit(): void
    {
        $halfUp = $this->calculate(
            $this->eur('0.10'),
            FeeRule::make()->percent(Percentage::of('5'), 'charge', RoundingMode::HalfUp),
        );

        $down = $this->calculate(
            $this->eur('0.10'),
            FeeRule::make()->percent(Percentage::of('5'), 'charge', RoundingMode::Down),
        );

        self::assertSame('0.01', $halfUp->totalFee()->toDecimalString());
        self::assertSame('0.00', $down->totalFee()->toDecimalString());
    }

    public function testAllRoundingModesAreMapped(): void
    {
        $mappedNames = [];

        foreach (RoundingMode::cases() as $roundingMode) {
            $mappedNames[] = $roundingMode->toBrick()->name;
        }

        self::assertCount(count(RoundingMode::cases()), array_unique($mappedNames));
    }

    public function testMaximumCannotBeLowerThanMinimum(): void
    {
        $this->expectException(InvalidFeeRule::class);

        $this->calculate(
            $this->eur('100.00'),
            FeeRule::make()
                ->min($this->eur('10.00'))
                ->max($this->eur('5.00')),
        );
    }

    private function calculate(Money $amount, FeeRule $rule): \Crystal\Finance\Core\Fee\FeeResult
    {
        return (new FeeCalculator())->calculate(
            $amount,
            $rule,
            FeeContext::make('test_operation', ['test' => true]),
        );
    }

    private function eur(string $amount): Money
    {
        return Money::of($amount, 'EUR', AssetRegistry::default());
    }
}
