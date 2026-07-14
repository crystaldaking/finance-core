<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

use Crystal\Finance\Core\Exception\InvalidLedgerTransaction;

final readonly class LedgerReversal
{
    public function __construct(
        private LedgerTransaction $original,
        private LedgerTransaction $reversal,
    ) {
        $reversalOf = $reversal->reversalOf();
        $reversedBy = $original->reversedBy();

        if (!$reversal->isReversal()
            || $reversalOf === null
            || !$reversalOf->equals($original->id())
            || $reversedBy === null
            || !$reversedBy->equals($reversal->id())
            || !self::entriesAreOpposite($original, $reversal)
        ) {
            throw InvalidLedgerTransaction::invalidReversalPair();
        }
    }

    public function original(): LedgerTransaction
    {
        return $this->original;
    }

    public function reversal(): LedgerTransaction
    {
        return $this->reversal;
    }

    private static function entriesAreOpposite(LedgerTransaction $original, LedgerTransaction $reversal): bool
    {
        $originalEntries = $original->entries();
        $reversalEntries = $reversal->entries();

        if (count($originalEntries) !== count($reversalEntries)) {
            return false;
        }

        foreach ($originalEntries as $index => $originalEntry) {
            $reversalEntry = $reversalEntries[$index];

            if (!$originalEntry->accountId()->equals($reversalEntry->accountId())
                || $originalEntry->direction()->opposite() !== $reversalEntry->direction()
                || !$originalEntry->amount()->isCompatibleWith($reversalEntry->amount())
                || $originalEntry->amount()->compareTo($reversalEntry->amount()) !== 0
            ) {
                return false;
            }
        }

        return true;
    }
}
