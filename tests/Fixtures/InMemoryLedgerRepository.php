<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Fixtures;

use Crystal\Finance\Core\Exception\InvalidLedgerTransaction;
use Crystal\Finance\Core\Ledger\LedgerReference;
use Crystal\Finance\Core\Ledger\LedgerRepository;
use Crystal\Finance\Core\Ledger\LedgerTransaction;

final class InMemoryLedgerRepository implements LedgerRepository
{
    /**
     * @var array<string, LedgerTransaction>
     */
    private array $transactionsByReference = [];

    #[\Override]
    public function append(LedgerTransaction $transaction): void
    {
        $reference = $transaction->reference()->value();

        if (isset($this->transactionsByReference[$reference])) {
            throw InvalidLedgerTransaction::duplicateReference($reference);
        }

        $this->transactionsByReference[$reference] = $transaction;
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
