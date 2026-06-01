<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Money;

use Brick\Math\RoundingMode as BrickRoundingMode;

enum RoundingMode: string
{
    case Unnecessary = 'unnecessary';
    case Up = 'up';
    case Down = 'down';
    case Ceiling = 'ceiling';
    case Floor = 'floor';
    case HalfUp = 'half_up';
    case HalfDown = 'half_down';
    case HalfCeiling = 'half_ceiling';
    case HalfFloor = 'half_floor';
    case HalfEven = 'half_even';

    public function toBrick(): BrickRoundingMode
    {
        return match ($this) {
            self::Unnecessary => BrickRoundingMode::Unnecessary,
            self::Up => BrickRoundingMode::Up,
            self::Down => BrickRoundingMode::Down,
            self::Ceiling => BrickRoundingMode::Ceiling,
            self::Floor => BrickRoundingMode::Floor,
            self::HalfUp => BrickRoundingMode::HalfUp,
            self::HalfDown => BrickRoundingMode::HalfDown,
            self::HalfCeiling => BrickRoundingMode::HalfCeiling,
            self::HalfFloor => BrickRoundingMode::HalfFloor,
            self::HalfEven => BrickRoundingMode::HalfEven,
        };
    }
}
