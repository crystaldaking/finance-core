<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

final readonly class LedgerReversal
{
    public function __construct(
        private LedgerTransaction $original,
        private LedgerTransaction $reversal,
    ) {
    }

    public function original(): LedgerTransaction
    {
        return $this->original;
    }

    public function reversal(): LedgerTransaction
    {
        return $this->reversal;
    }
}
