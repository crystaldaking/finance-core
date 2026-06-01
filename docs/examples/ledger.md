# Ledger Example

Ledger transactions contain positive debit and credit entries and must balance per asset.

```php
use Crystal\Finance\Core\Ledger\LedgerAccountId;
use Crystal\Finance\Core\Ledger\LedgerReference;
use Crystal\Finance\Core\Ledger\LedgerTransaction;
use Crystal\Finance\Core\Ledger\LedgerTransactionType;
use Crystal\Finance\Core\Ledger\LedgerValidator;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;

$registry = AssetRegistry::default();

$transaction = LedgerTransaction::make(
    LedgerTransactionType::Transfer,
    LedgerReference::manual('transfer_123'),
)
    ->debit(LedgerAccountId::fromString('cash:main'), Money::of('100.00', 'EUR', $registry))
    ->credit(LedgerAccountId::fromString('equity:owner'), Money::of('100.00', 'EUR', $registry));

(new LedgerValidator())->assertValid($transaction);

$reversal = $transaction->reverse(LedgerReference::manual('transfer_123_reversal'));
```

Accounts are opaque identifiers. The core does not infer an asset from the account id.
