<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

use Crystal\Finance\Core\Exception\InvalidLedgerTransaction;

final readonly class Ledger
{
    public function __construct(
        private LedgerRepository $repository,
        private LedgerValidator $validator = new LedgerValidator(),
    ) {
    }

    public function append(LedgerTransaction $transaction): void
    {
        $this->validator->assertValid($transaction);

        if ($transaction->isReversal()) {
            throw InvalidLedgerTransaction::reversalRequiresAtomicPersistence();
        }

        $this->repository->append($transaction);
    }

    public function appendReversal(LedgerReversal $reversal): void
    {
        $this->validator->assertValid($reversal->original());

        if (!$this->repository instanceof LedgerReversalRepository) {
            throw InvalidLedgerTransaction::reversalRequiresAtomicPersistence();
        }

        $this->repository->appendReversal($reversal);
    }
}
