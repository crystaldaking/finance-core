<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Crystal\Finance\Core\Money\AssetPair;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\ExchangeRate;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Rate;
use Crystal\Finance\Core\Money\RoundingMode;

$registry = AssetRegistry::default();

$rate = ExchangeRate::of(
    AssetPair::of($registry->get('EUR'), $registry->get('USD')),
    Rate::of('1.0834'),
);

$invoice = Money::of('100.00', 'EUR', $registry);
$converted = $rate->convert($invoice, RoundingMode::HalfUp);

echo $invoice->toDecimalString() . ' EUR -> ' . $converted->toDecimalString() . ' USD' . PHP_EOL;
echo 'Rate: ' . $rate->rate()->toDecimalString() . PHP_EOL;
