<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Fixtures;

use Crystal\Finance\Core\Exception\InvalidLedgerTransaction;
use Crystal\Finance\Core\Ledger\LedgerReference;
use Crystal\Finance\Core\Ledger\LedgerReversal;
use Crystal\Finance\Core\Ledger\LedgerReversalRepository;
use Crystal\Finance\Core\Ledger\LedgerTransaction;

final class InMemoryLedgerRepository implements LedgerReversalRepository
{
    /**
     * @var array<string, LedgerTransaction>
     */
    private array $transactionsByReference = [];

    #[\Override]
    public function append(LedgerTransaction $transaction): void
    {
        if ($transaction->isReversal()) {
            throw InvalidLedgerTransaction::reversalRequiresAtomicPersistence();
        }

        $reference = $transaction->reference()->value();

        if (isset($this->transactionsByReference[$reference])) {
            throw InvalidLedgerTransaction::duplicateReference($reference);
        }

        $this->transactionsByReference[$reference] = $transaction;
    }

    #[\Override]
    public function appendReversal(LedgerReversal $reversal): void
    {
        $markedOriginal = $reversal->original();
        $reversalTransaction = $reversal->reversal();
        $originalReference = $markedOriginal->reference()->value();
        $reversalReference = $reversalTransaction->reference()->value();
        $storedOriginal = $this->transactionsByReference[$originalReference] ?? null;

        if ($storedOriginal === null || !$storedOriginal->id()->equals($markedOriginal->id())) {
            throw InvalidLedgerTransaction::invalidReversalPair();
        }

        if ($storedOriginal->reversedBy() !== null) {
            throw InvalidLedgerTransaction::alreadyReversed($storedOriginal->id()->value());
        }

        foreach ($this->transactionsByReference as $transaction) {
            if ($transaction->reversalOf()?->equals($storedOriginal->id()) === true) {
                throw InvalidLedgerTransaction::alreadyReversed($storedOriginal->id()->value());
            }
        }

        if (isset($this->transactionsByReference[$reversalReference])) {
            throw InvalidLedgerTransaction::duplicateReference($reversalReference);
        }

        $this->transactionsByReference[$originalReference] = $markedOriginal;
        $this->transactionsByReference[$reversalReference] = $reversalTransaction;
    }

    #[\Override]
    public function findByReference(LedgerReference $reference): ?LedgerTransaction
    {
        return $this->transactionsByReference[$reference->value()] ?? null;
    }

    #[\Override]
    public function existsByReference(LedgerReference $reference): bool
    {
        return isset($this->transactionsByReference[$reference->value()]);
    }
}
