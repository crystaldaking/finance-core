<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

use Crystal\Finance\Core\Money\Money;
use DateTimeImmutable;

final readonly class LedgerBalance
{
    public function __construct(
        private LedgerAccountId $accountId,
        private Money $balance,
        private ?DateTimeImmutable $asOf = null,
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

    public function asOf(): ?DateTimeImmutable
    {
        return $this->asOf ?? null;
    }
}
