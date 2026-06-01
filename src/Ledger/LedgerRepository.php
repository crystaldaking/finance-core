<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

interface LedgerRepository
{
    public function append(LedgerTransaction $transaction): void;

    public function findByReference(LedgerReference $reference): ?LedgerTransaction;

    public function existsByReference(LedgerReference $reference): bool;
}
