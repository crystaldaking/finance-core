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
$eurFloat = Money::of('500.00', 'EUR', $registry);
$tronFloat = Money::of('250.000000', 'USDT@TRON', $registry);

$transaction = LedgerTransaction::make(
    LedgerTransactionType::Journal,
    LedgerReference::of('treasury', 'daily_float_reclassification_001'),
)
    ->debit(LedgerAccountId::fromString('asset:bank:eur'), $eurFloat)
    ->credit(LedgerAccountId::fromString('liability:treasury:eur_float'), $eurFloat)
    ->debit(LedgerAccountId::fromString('asset:wallet:tron'), $tronFloat)
    ->credit(LedgerAccountId::fromString('liability:treasury:usdt_tron_float'), $tronFloat);

(new LedgerValidator())->assertValid($transaction);

echo 'Reference: ' . $transaction->reference()->value() . PHP_EOL;
echo 'EUR float: ' . $eurFloat->toDecimalString() . PHP_EOL;
echo 'USDT float: ' . $tronFloat->toDecimalString() . PHP_EOL;
echo 'Balances per asset: yes' . PHP_EOL;
