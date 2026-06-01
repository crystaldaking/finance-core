# Fees Example

Fees are deterministic calculations that return gross, total fee, net, and a breakdown.

```php
use Crystal\Finance\Core\Fee\FeeCalculator;
use Crystal\Finance\Core\Fee\FeeContext;
use Crystal\Finance\Core\Fee\FeeRule;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Percentage;

$registry = AssetRegistry::default();

$rule = FeeRule::make()
    ->percent(Percentage::of('2.5'), 'service_fee')
    ->fixed(Money::of('0.30', 'EUR', $registry), 'fixed_charge')
    ->min(Money::of('1.00', 'EUR', $registry))
    ->max(Money::of('50.00', 'EUR', $registry));

$result = (new FeeCalculator())->calculate(
    Money::of('100.00', 'EUR', $registry),
    $rule,
    FeeContext::make('invoice_collection'),
);
```

Maximum caps are represented in the breakdown as a negative adjustment line so the sum of lines still explains the final total fee.
