<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Crystal\Finance\Core\Audit\Actor;
use Crystal\Finance\Core\Audit\AuditContext;
use Crystal\Finance\Core\Event\EventMetadata;
use Crystal\Finance\Core\Event\GenericDomainEvent;
use Crystal\Finance\Core\Fee\FeeCalculator;
use Crystal\Finance\Core\Fee\FeeContext;
use Crystal\Finance\Core\Fee\FeeRule;
use Crystal\Finance\Core\Idempotency\PayloadFingerprint;
use Crystal\Finance\Core\Ledger\LedgerAccountId;
use Crystal\Finance\Core\Ledger\LedgerReference;
use Crystal\Finance\Core\Ledger\LedgerTransaction;
use Crystal\Finance\Core\Ledger\LedgerTransactionType;
use Crystal\Finance\Core\Ledger\LedgerValidator;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Percentage;

$registry = AssetRegistry::default();
$gross = Money::of('250.000000', 'USDT@TRON', $registry);

$feeResult = (new FeeCalculator())->calculate(
    $gross,
    FeeRule::make()
        ->percent(Percentage::of('2.4'), 'platform_variable_fee')
        ->fixed(Money::of('0.500000', 'USDT@TRON', $registry), 'platform_fixed_fee')
        ->max(Money::of('15.000000', 'USDT@TRON', $registry)),
    FeeContext::make('merchant_collection', ['merchant_id' => 'merchant_123']),
);

$transaction = LedgerTransaction::make(
    LedgerTransactionType::Fee,
    LedgerReference::of('collection', 'merchant_123_2026_0001'),
)
    ->debit(LedgerAccountId::fromString('asset:wallet:tron_settlement'), $gross)
    ->credit(LedgerAccountId::fromString('liability:merchant_123:available'), $feeResult->net())
    ->credit(LedgerAccountId::fromString('revenue:platform:fees'), $feeResult->totalFee());

(new LedgerValidator())->assertValid($transaction);

$audit = AuditContext::record(
    Actor::system(),
    'Merchant collection was posted to the finance ledger',
    new DateTimeImmutable('2026-06-01T00:00:00+00:00'),
    metadata: ['merchant_id' => 'merchant_123'],
);

$event = GenericDomainEvent::record(
    'Ledger.TransactionPosted',
    $transaction->id()->value(),
    $audit->occurredAt(),
    EventMetadata::fromArray([
        'reference' => $transaction->reference()->value(),
        'gross_minor' => $feeResult->gross()->toMinorUnitString(),
        'fee_minor' => $feeResult->totalFee()->toMinorUnitString(),
        'net_minor' => $feeResult->net()->toMinorUnitString(),
    ]),
    $audit,
);

$fingerprint = PayloadFingerprint::fromArray([
    'operation' => 'merchant_collection',
    'reference' => $transaction->reference()->value(),
    'gross' => $feeResult->gross()->toDecimalString(),
    'asset' => $feeResult->gross()->asset()->id()->value(),
]);

echo 'Gross: ' . $feeResult->gross()->toDecimalString() . ' ' . $feeResult->gross()->asset()->id()->value() . PHP_EOL;
echo 'Merchant net: ' . $feeResult->net()->toDecimalString() . PHP_EOL;
echo 'Platform fee: ' . $feeResult->totalFee()->toDecimalString() . PHP_EOL;
echo 'Ledger entries: ' . count($transaction->entries()) . PHP_EOL;
echo 'Event: ' . $event->eventName() . PHP_EOL;
echo 'Fingerprint: ' . $fingerprint->value() . PHP_EOL;
