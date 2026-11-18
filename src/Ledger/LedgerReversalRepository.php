<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

interface LedgerReversalRepository extends LedgerRepository
{
    /**
     * Atomically mark the stored original as reversed and append the reversal.
     *
     * Reject a missing or already reversed original, duplicate reversal id or reference,
     * or reversal entries that do not offset the stored original entries.
     * Preserve the stored original facts and update only its reversal marker.
     */
    public function appendReversal(LedgerReversal $reversal): void;
}
