<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

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
        $this->repository->append($transaction);
    }
}
