<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Fee;

use Crystal\Finance\Core\Exception\InvalidFeeResult;
use Crystal\Finance\Core\Fee\FeeBreakdown;
use Crystal\Finance\Core\Fee\FeeBreakdownLine;
use Crystal\Finance\Core\Fee\FeeComponentType;
use Crystal\Finance\Core\Fee\FeeContext;
use Crystal\Finance\Core\Fee\FeeResult;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;
use PHPUnit\Framework\TestCase;

final class FeeResultTest extends TestCase
{
    public function testDerivesNetFromGrossAndTotalFee(): void
    {
        $result = FeeResult::of(
            Money::of('100.00', 'EUR', AssetRegistry::default()),
            Money::of('2.50', 'EUR', AssetRegistry::default()),
            FeeBreakdown::of(
                FeeBreakdownLine::component('service_fee', FeeComponentType::Fixed, Money::of('2.50', 'EUR', AssetRegistry::default())),
            ),
            FeeContext::make('test_operation'),
        );

        self::assertSame('97.50', $result->net()->toDecimalString());
    }

    public function testRejectsBreakdownThatDoesNotSumToTotalFee(): void
    {
        $this->expectException(InvalidFeeResult::class);

        FeeResult::of(
            Money::of('100.00', 'EUR', AssetRegistry::default()),
            Money::of('2.50', 'EUR', AssetRegistry::default()),
            FeeBreakdown::of(
                FeeBreakdownLine::component('service_fee', FeeComponentType::Fixed, Money::of('2.00', 'EUR', AssetRegistry::default())),
            ),
            FeeContext::make('test_operation'),
        );
    }

    public function testRejectsBreakdownLineInDifferentAsset(): void
    {
        $this->expectException(InvalidFeeResult::class);

        FeeResult::of(
            Money::of('100.00', 'EUR', AssetRegistry::default()),
            Money::of('2.50', 'EUR', AssetRegistry::default()),
            FeeBreakdown::of(
                FeeBreakdownLine::component('service_fee', FeeComponentType::Fixed, Money::of('2.50', 'USD', AssetRegistry::default())),
            ),
            FeeContext::make('test_operation'),
        );
    }

    public function testConstructorRejectsInconsistentNet(): void
    {
        $this->expectException(InvalidFeeResult::class);

        new FeeResult(
            Money::of('100.00', 'EUR', AssetRegistry::default()),
            Money::of('2.50', 'EUR', AssetRegistry::default()),
            Money::of('98.00', 'EUR', AssetRegistry::default()),
            FeeBreakdown::of(
                FeeBreakdownLine::component('service_fee', FeeComponentType::Fixed, Money::of('2.50', 'EUR', AssetRegistry::default())),
            ),
            FeeContext::make('test_operation'),
        );
    }

    public function testBreakdownConstructorRejectsInvalidLines(): void
    {
        $this->expectException(InvalidFeeResult::class);

        $this->makeBreakdownWithUncheckedLines(['not-a-line']);
    }

    /**
     * @param array<array-key, mixed> $lines
     */
    private function makeBreakdownWithUncheckedLines(array $lines): void
    {
        (new \ReflectionClass(FeeBreakdown::class))->newInstance($lines);
    }
}
