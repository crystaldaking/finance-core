<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Crystal\Finance\Core\Fee\FeeCalculator;
use Crystal\Finance\Core\Fee\FeeContext;
use Crystal\Finance\Core\Fee\FeeRule;
use Crystal\Finance\Core\Ledger\LedgerAccountId;
use Crystal\Finance\Core\Ledger\LedgerReference;
use Crystal\Finance\Core\Ledger\LedgerTransaction;
use Crystal\Finance\Core\Ledger\LedgerTransactionType;
use Crystal\Finance\Core\Ledger\LedgerValidator;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Percentage;

$registry = AssetRegistry::default();
$gross = Money::of('100.000000', 'USDT@TRON', $registry);

$feeResult = (new FeeCalculator())->calculate(
    $gross,
    FeeRule::make()->percent(Percentage::of('3'), 'platform_fee'),
    FeeContext::make('merchant_balance_update', ['merchant_id' => 'merchant_123']),
);

$transaction = LedgerTransaction::make(
    LedgerTransactionType::Fee,
    LedgerReference::manual('merchant_123_deposit_001'),
)
    ->debit(LedgerAccountId::fromString('asset:wallet:tron_settlement'), $gross)
    ->credit(LedgerAccountId::fromString('liability:merchant_123:available'), $feeResult->net())
    ->credit(LedgerAccountId::fromString('revenue:platform:fees'), $feeResult->totalFee());

(new LedgerValidator())->assertValid($transaction);

echo 'Merchant net: ' . $feeResult->net()->toDecimalString() . PHP_EOL;
echo 'Platform fee: ' . $feeResult->totalFee()->toDecimalString() . PHP_EOL;
