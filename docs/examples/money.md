# Money Example

```php
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Percentage;
use Crystal\Finance\Core\Money\RoundingMode;

$registry = AssetRegistry::default();
$amount = Money::of('100.00', $registry->get('EUR'));
$fee = $amount->percentage(Percentage::of('2.5'), RoundingMode::HalfUp);
$total = $amount->plus($fee);

$eth = Money::ofMinor('1123456789123456789', 'ETH@ETHEREUM', $registry);

$parts = Money::of('10.00', 'EUR', $registry)->allocate([1, 1, 1]);
```

`Money::toMinorUnitString()` returns a string so values such as 18-decimal ETH amounts do not overflow native integers.
