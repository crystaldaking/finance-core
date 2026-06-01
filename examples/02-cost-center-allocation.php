<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;

$registry = AssetRegistry::default();
$cloudInvoice = Money::of('2480.00', 'EUR', $registry);

$parts = $cloudInvoice->allocate([50, 30, 20]);
$product = $parts[0] ?? throw new RuntimeException('Missing product allocation.');
$operations = $parts[1] ?? throw new RuntimeException('Missing operations allocation.');
$data = $parts[2] ?? throw new RuntimeException('Missing data allocation.');

echo 'Product cost center: ' . $product->toDecimalString() . PHP_EOL;
echo 'Operations cost center: ' . $operations->toDecimalString() . PHP_EOL;
echo 'Data cost center: ' . $data->toDecimalString() . PHP_EOL;
echo 'Allocated total: ' . $product->plus($operations)->plus($data)->toDecimalString() . PHP_EOL;
