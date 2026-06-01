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

$transaction = LedgerTransaction::make(
    LedgerTransactionType::Journal,
    LedgerReference::manual('office_supplies_2026_0001'),
)
    ->debit(
        LedgerAccountId::fromString('expense:office_supplies'),
        Money::of('84.25', 'EUR', $registry),
    )
    ->credit(
        LedgerAccountId::fromString('asset:bank:operating'),
        Money::of('84.25', 'EUR', $registry),
    );

(new LedgerValidator())->assertValid($transaction);

echo 'Corporate expense journal is balanced.' . PHP_EOL;
