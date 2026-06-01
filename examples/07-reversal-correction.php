<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Crystal\Finance\Core\Ledger\LedgerAccountId;
use Crystal\Finance\Core\Ledger\LedgerReference;
use Crystal\Finance\Core\Ledger\LedgerTransaction;
use Crystal\Finance\Core\Ledger\LedgerTransactionId;
use Crystal\Finance\Core\Ledger\LedgerTransactionType;
use Crystal\Finance\Core\Ledger\LedgerValidator;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;

$registry = AssetRegistry::default();

$original = LedgerTransaction::make(
    LedgerTransactionType::Adjustment,
    LedgerReference::of('correction', 'wrong_adjustment_001'),
    id: LedgerTransactionId::fromString('ltx_wrong_adjustment_001'),
)
    ->debit(LedgerAccountId::fromString('asset:bank:operating'), Money::of('25.00', 'EUR', $registry))
    ->credit(LedgerAccountId::fromString('income:misc'), Money::of('25.00', 'EUR', $registry));

$reversalResult = $original->reverse(
    LedgerReference::of('correction', 'wrong_adjustment_001_reversal'),
    LedgerTransactionId::fromString('ltx_wrong_adjustment_001_reversal'),
);

$markedOriginal = $reversalResult->original();
$reversal = $reversalResult->reversal();
$reversedBy = $markedOriginal->reversedBy() ?? throw new RuntimeException('Original transaction is not marked as reversed.');

(new LedgerValidator())->assertValid($reversal);

echo 'Original reversed by: ' . $reversedBy->value() . PHP_EOL;
echo 'Reversal reference: ' . $reversal->reference()->value() . PHP_EOL;
echo 'Reversal balances: yes' . PHP_EOL;
