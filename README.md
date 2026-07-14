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

Reversals are created with `LedgerTransaction::reverse()` and persisted with `Ledger::appendReversal()` through a `LedgerReversalRepository`. This keeps marking the original and appending its single reversal atomic.

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
Idempotency TTLs must be positive. The replay-retention window starts when the callback completes; the initial processing lease should cover the maximum expected callback duration. Store adapters compare record claim ids to fence stale completion and failure attempts.

## Quality

```bash
composer qa
composer coverage
composer mutation
```

The QA suite validates Composer metadata, coding style, PHPStan max level, Psalm strict analysis, PHPUnit unit tests, and architecture boundaries. The hardening suite adds line coverage and mutation testing:

```bash
composer hardening
```

## Examples

The `examples/` directory contains executable real-life usage scenarios:

- network-aware money boundaries and high-precision crypto minor units
- SaaS cost-center allocation with deterministic residual handling
- corporate expense journals
- multi-asset treasury ledger entries
- invoice fee calculation with explainable breakdown lines
- merchant collection ledger posting with audit, event, and idempotency fingerprint
- reversal-based correction workflow
- idempotent ledger command execution
- FX conversion with explicit rounding
- audit event recording for outbox-style integrations

`examples/06-platform-fee-ledger-posting.php` is the integration proof: it combines money, fees, ledger posting, audit context, a domain event, and an idempotency fingerprint in one merchant finance scenario.

See `examples/README.md` for the full runnable scenario list.

## Release Governance

- Backward compatibility policy: `docs/backward-compatibility.md`
- Public API contract: `docs/public-api.md`
- Release checklist: `docs/release/checklist.md`
- Changelog: `CHANGELOG.md`
