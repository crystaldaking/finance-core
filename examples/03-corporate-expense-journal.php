<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Crystal\Finance\Core\Ledger\LedgerAccountId;
use Crystal\Finance\Core\Ledger\LedgerReference;
use Crystal\Finance\Core\Ledger\LedgerTransaction;
use Crystal\Finance\Core\Ledger\LedgerTransactionType;
use Crystal\Finance\Core\Ledger\LedgerValidator;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;

$registry = AssetRegistry::default();
$amount = Money::of('84.25', 'EUR', $registry);

$transaction = LedgerTransaction::make(
    LedgerTransactionType::Journal,
    LedgerReference::of('expense', 'office_supplies_2026_0001'),
)
    ->debit(
        LedgerAccountId::fromString('expense:office_supplies'),
        $amount,
    )
    ->credit(
        LedgerAccountId::fromString('asset:bank:operating'),
        $amount,
    );

(new LedgerValidator())->assertValid($transaction);

echo 'Expense reference: ' . $transaction->reference()->value() . PHP_EOL;
echo 'Expense amount: ' . $amount->toDecimalString() . PHP_EOL;
echo 'Journal entries: ' . count($transaction->entries()) . PHP_EOL;
