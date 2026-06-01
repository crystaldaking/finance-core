<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Idempotency;

enum IdempotencyStatus: string
{
    case Started = 'started';
    case Completed = 'completed';
    case Failed = 'failed';
    case Expired = 'expired';
}
