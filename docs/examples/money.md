# Money Example

```php
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;

$registry = AssetRegistry::default();
$amount = Money::of('100.00', $registry->get('EUR'));
```
