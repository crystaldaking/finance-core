<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

interface LedgerReversalRepository extends LedgerRepository
{
    /**
     * Atomically persist the marked original and its reversal.
     *
     * Implementations must reject a reversal when the stored original is missing,
     * has already been reversed, or already has a reversal transaction.
     */
    public function appendReversal(LedgerReversal $reversal): void;
}
