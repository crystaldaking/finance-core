<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

use Crystal\Finance\Core\Exception\InvalidLedgerTransaction;
use Crystal\Finance\Core\Money\Money;

final readonly class LedgerEntry
{
    private function __construct(
        private LedgerEntryId $id,
        private LedgerAccountId $accountId,
        private LedgerDirection $direction,
        private Money $amount,
    ) {
        if (!$amount->isPositive()) {
            throw InvalidLedgerTransaction::nonPositiveEntry($amount->toDecimalString());
        }
    }

    public static function debit(LedgerAccountId $accountId, Money $amount, ?LedgerEntryId $id = null): self
    {
        return new self($id ?? LedgerEntryId::generate(), $accountId, LedgerDirection::Debit, $amount);
    }

    public static function credit(LedgerAccountId $accountId, Money $amount, ?LedgerEntryId $id = null): self
    {
        return new self($id ?? LedgerEntryId::generate(), $accountId, LedgerDirection::Credit, $amount);
    }

    public function reversed(?LedgerEntryId $id = null): self
    {
        return new self($id ?? LedgerEntryId::generate(), $this->accountId, $this->direction->opposite(), $this->amount);
    }

    public function id(): LedgerEntryId
    {
        return $this->id;
    }

    public function accountId(): LedgerAccountId
    {
        return $this->accountId;
    }

    public function direction(): LedgerDirection
    {
        return $this->direction;
    }

    public function amount(): Money
    {
        return $this->amount;
    }
}
