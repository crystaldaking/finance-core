<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Money;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Crystal\Finance\Core\Exception\InvalidMoneyAmount;
use Crystal\Finance\Core\Exception\InvalidPercentage;
use Crystal\Finance\Core\Exception\InvalidRate;

final readonly class DecimalString
{
    private const string DECIMAL_PATTERN = '/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/';

    private function __construct()
    {
    }

    public static function money(string $value): BigDecimal
    {
        if (preg_match(self::DECIMAL_PATTERN, $value) !== 1) {
            throw InvalidMoneyAmount::fromString($value);
        }

        try {
            return BigDecimal::of($value);
        } catch (MathException) {
            throw InvalidMoneyAmount::fromString($value);
        }
    }

    public static function percentage(string $value): BigDecimal
    {
        if (preg_match(self::DECIMAL_PATTERN, $value) !== 1) {
            throw InvalidPercentage::fromString($value);
        }

        try {
            return BigDecimal::of($value);
        } catch (MathException) {
            throw InvalidPercentage::fromString($value);
        }
    }

    public static function rate(string $value): BigDecimal
    {
        if (preg_match(self::DECIMAL_PATTERN, $value) !== 1) {
            throw InvalidRate::fromString($value);
        }

        try {
            return BigDecimal::of($value);
        } catch (MathException) {
            throw InvalidRate::fromString($value);
        }
    }
}
