<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Invariants;

use Crystal\Finance\Core\Exception\InvalidFeeRule;
use Crystal\Finance\Core\Fee\FeeCalculator;
use Crystal\Finance\Core\Fee\FeeContext;
use Crystal\Finance\Core\Fee\FeeResult;
use Crystal\Finance\Core\Fee\FeeRule;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Percentage;
use PHPUnit\Framework\TestCase;

final class FeeInvariantTest extends TestCase
{
    public function testGrossAlwaysEqualsNetPlusTotalFee(): void
    {
        foreach ($this->feeCases() as [$gross, $rule]) {
            $result = $this->calculate($gross, $rule);

            self::assertSame(
                $result->gross()->toMinorUnitString(),
                $result->net()->plus($result->totalFee())->toMinorUnitString(),
            );
        }
    }

    public function testBreakdownLinesAlwaysSumToTotalFeeIncludingAdjustments(): void
    {
        foreach ($this->feeCases() as [$gross, $rule]) {
            $result = $this->calculate($gross, $rule);
            $lineTotal = Money::zero($result->gross()->asset());

            foreach ($result->breakdown()->lines() as $line) {
                $lineTotal = $lineTotal->plus($line->amount());
            }

            self::assertSame($result->totalFee()->toMinorUnitString(), $lineTotal->toMinorUnitString());
        }
    }

    public function testMinimumAndMaximumClampTheTotalFeeDeterministically(): void
    {
        $rule = FeeRule::make()
            ->percent(Percentage::of('10'), 'variable')
            ->min($this->eur('1.00'))
            ->max($this->eur('5.00'));

        self::assertSame('1.00', $this->calculate($this->eur('5.00'), $rule)->totalFee()->toDecimalString());
        self::assertSame('3.00', $this->calculate($this->eur('30.00'), $rule)->totalFee()->toDecimalString());
        self::assertSame('5.00', $this->calculate($this->eur('100.00'), $rule)->totalFee()->toDecimalString());
    }

    public function testNetCannotBecomeNegativeUnlessExplicitlyAllowed(): void
    {
        $amounts = ['0.01', '1.00', '10.00'];
        $rejections = 0;

        foreach ($amounts as $amount) {
            try {
                $this->calculate($this->eur($amount), FeeRule::make()->fixed($this->eur('10.01')));
                self::fail('Expected negative net rejection for ' . $amount);
            } catch (InvalidFeeRule) {
                $rejections++;
            }
        }

        self::assertSame(count($amounts), $rejections);

        $allowed = $this->calculate(
            $this->eur('1.00'),
            FeeRule::make()->fixed($this->eur('10.01'))->allowNegativeNet(),
        );

        self::assertSame('-9.01', $allowed->net()->toDecimalString());
    }

    /**
     * @return list<array{0: Money, 1: FeeRule}>
     */
    private function feeCases(): array
    {
        return [
            [
                $this->eur('100.00'),
                FeeRule::make()
                    ->percent(Percentage::of('2.5'), 'variable')
                    ->fixed($this->eur('0.30'), 'fixed'),
            ],
            [
                $this->eur('1.00'),
                FeeRule::make()
                    ->percent(Percentage::of('1'), 'variable')
                    ->min($this->eur('0.10')),
            ],
            [
                $this->eur('1000.00'),
                FeeRule::make()
                    ->percent(Percentage::of('10'), 'variable')
                    ->max($this->eur('50.00')),
            ],
            [
                Money::of('100.000000', 'USDT@TRON', AssetRegistry::default()),
                FeeRule::make()
                    ->percent(Percentage::of('0.75'), 'variable')
                    ->fixed(Money::of('0.100000', 'USDT@TRON', AssetRegistry::default()), 'fixed'),
            ],
        ];
    }

    private function calculate(Money $amount, FeeRule $rule): FeeResult
    {
        return (new FeeCalculator())->calculate($amount, $rule, FeeContext::make('invariant'));
    }

    private function eur(string $amount): Money
    {
        return Money::of($amount, 'EUR', AssetRegistry::default());
    }
}
