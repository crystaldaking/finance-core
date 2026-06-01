<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Money;

enum AssetType: string
{
    case Fiat = 'fiat';
    case Crypto = 'crypto';
}
