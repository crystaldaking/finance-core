<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

enum LedgerTransactionType: string
{
    case Journal = 'journal';
    case Transfer = 'transfer';
    case Fee = 'fee';
    case Adjustment = 'adjustment';
    case OpeningBalance = 'opening_balance';
    case Reversal = 'reversal';
}
