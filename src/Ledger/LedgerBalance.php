<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

use Crystal\Finance\Core\Money\Money;

final readonly class LedgerBalance
{
    public function __construct(
        private LedgerAccountId $accountId,
        private Money $balance,
    ) {
    }

    public function accountId(): LedgerAccountId
    {
        return $this->accountId;
    }

    public function balance(): Money
    {
        return $this->balance;
    }
}
