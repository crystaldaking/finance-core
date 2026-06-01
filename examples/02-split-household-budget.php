<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;

$registry = AssetRegistry::default();
$monthlyRent = Money::of('1350.00', 'EUR', $registry);

$parts = $monthlyRent->allocate([2, 1, 1]);
$primaryTenant = $parts[0] ?? throw new RuntimeException('Missing primary tenant allocation.');
$secondTenant = $parts[1] ?? throw new RuntimeException('Missing second tenant allocation.');
$thirdTenant = $parts[2] ?? throw new RuntimeException('Missing third tenant allocation.');

echo 'Primary tenant: ' . $primaryTenant->toDecimalString() . PHP_EOL;
echo 'Second tenant: ' . $secondTenant->toDecimalString() . PHP_EOL;
echo 'Third tenant: ' . $thirdTenant->toDecimalString() . PHP_EOL;
echo 'Allocated total: ' . $primaryTenant->plus($secondTenant)->plus($thirdTenant)->toDecimalString() . PHP_EOL;
