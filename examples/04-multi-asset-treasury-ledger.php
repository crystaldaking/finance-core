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
    LedgerReference::manual('treasury_reclassification_001'),
)
    ->debit(LedgerAccountId::fromString('asset:bank:eur'), Money::of('500.00', 'EUR', $registry))
    ->credit(LedgerAccountId::fromString('liability:treasury:eur'), Money::of('500.00', 'EUR', $registry))
    ->debit(LedgerAccountId::fromString('asset:wallet:tron'), Money::of('250.000000', 'USDT@TRON', $registry))
    ->credit(LedgerAccountId::fromString('liability:treasury:usdt_tron'), Money::of('250.000000', 'USDT@TRON', $registry));

(new LedgerValidator())->assertValid($transaction);

echo 'Multi-asset transaction balances independently per asset.' . PHP_EOL;
