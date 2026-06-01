# Crystal Finance Core

`crystaldaking/finance-core` is a framework-agnostic PHP 8.5 financial core for applications that need safe money arithmetic, network-aware assets, double-entry ledgering, explainable fees, audit context, domain events, and idempotency primitives.

It can be used in PSPs, crypto acquiring systems, internal finance platforms, corporate accounting tools, reconciliation workers, CLI jobs, and plain PHP services. It is not a Laravel package, PSP application, database schema, report engine, tax engine, or UI.

Payment workflows, provider integrations, webhooks, refunds, chargebacks, payouts, and settlement flows belong in a future package such as `crystaldaking/payments-core`.

## Installation

```bash
composer require crystaldaking/finance-core
```

## Core Principles

- No floating point arithmetic for financial values.
- Assets are explicit and network-aware: `USDT@TRON` and `USDT@ETHEREUM` are different assets.
- Ledger balances are derived from entries. This package does not store balances directly.
- Ledger transactions are append-only domain facts and must balance per asset.
- The core has no Laravel, Symfony, Doctrine ORM, Carbon, database, queue, or framework dependency.
- Public APIs use explicit value objects instead of framework models or magic arrays.

## Money

```php
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Percentage;
use Crystal\Finance\Core\Money\RoundingMode;

$registry = AssetRegistry::default();

$amount = Money::of('100.00', 'EUR', $registry);
$fee = $amount->percentage(Percentage::of('2.5'), RoundingMode::HalfUp);
$total = $amount->plus($fee);

$usdtTron = Money::of('10.000000', 'USDT@TRON', $registry);
$usdtEthereum = Money::of('10.000000', 'USDT@ETHEREUM', $registry);
```

`$usdtTron` and `$usdtEthereum` cannot be added together because they are different assets.

## Fees

```php
use Crystal\Finance\Core\Fee\FeeCalculator;
use Crystal\Finance\Core\Fee\FeeContext;
use Crystal\Finance\Core\Fee\FeeRule;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Percentage;

$rule = FeeRule::make()
    ->percent(Percentage::of('2.5'), label: 'service_fee')
    ->fixed(Money::of('0.30', 'EUR', $registry), label: 'fixed_charge')
    ->min(Money::of('1.00', 'EUR', $registry))
    ->max(Money::of('50.00', 'EUR', $registry));

$result = (new FeeCalculator())->calculate(
    Money::of('100.00', 'EUR', $registry),
    $rule,
    FeeContext::make('invoice_collection', ['customer' => 'customer_123']),
);

$result->gross();      // 100.00 EUR
$result->totalFee();   // 2.80 EUR
$result->net();        // 97.20 EUR
$result->breakdown();  // explainable fee lines
```

## Ledger

```php
use Crystal\Finance\Core\Ledger\LedgerAccountId;
use Crystal\Finance\Core\Ledger\LedgerReference;
use Crystal\Finance\Core\Ledger\LedgerTransaction;
use Crystal\Finance\Core\Ledger\LedgerTransactionType;
use Crystal\Finance\Core\Ledger\LedgerValidator;
use Crystal\Finance\Core\Money\Money;

$transaction = LedgerTransaction::make(
    LedgerTransactionType::Transfer,
    LedgerReference::manual('transfer_123'),
)
    ->debit(
        LedgerAccountId::fromString('cash:main'),
        Money::of('100.00', 'EUR', $registry),
    )
    ->credit(
        LedgerAccountId::fromString('equity:owner'),
        Money::of('100.00', 'EUR', $registry),
    );

(new LedgerValidator())->assertValid($transaction);
```

## Idempotency

```php
use Crystal\Finance\Core\Idempotency\IdempotencyKey;
use Crystal\Finance\Core\Idempotency\IdempotencyScope;
use Crystal\Finance\Core\Idempotency\PayloadFingerprint;

$scope = IdempotencyScope::fromString('ledger:append');
$key = IdempotencyKey::fromString('request_12345678');
$fingerprint = PayloadFingerprint::fromArray([
    'reference' => 'transfer_123',
    'amount' => '100.00',
    'asset' => 'EUR',
]);
```

Concrete Redis, SQL, cache, and lock implementations live outside this package.

## Quality

```bash
composer qa
```

The QA suite validates Composer metadata, coding style, PHPStan max level, Psalm strict analysis, PHPUnit unit tests, and architecture boundaries.
