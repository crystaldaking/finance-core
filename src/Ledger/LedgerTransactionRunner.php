<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

interface LedgerTransactionRunner
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function run(callable $callback): mixed;
}
