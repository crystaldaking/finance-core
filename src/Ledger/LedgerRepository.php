<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

interface LedgerRepository
{
    /**
     * Append a new ordinary transaction with a unique id and reference.
     * Reversals and originals with a reversal marker require the atomic reversal path.
     */
    public function append(LedgerTransaction $transaction): void;

    public function findByReference(LedgerReference $reference): ?LedgerTransaction;

    public function existsByReference(LedgerReference $reference): bool;
}
