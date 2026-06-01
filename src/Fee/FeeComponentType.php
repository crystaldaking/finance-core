<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Fee;

enum FeeComponentType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
    case Minimum = 'minimum';
    case Maximum = 'maximum';
}
