<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Crystal\Finance\Core\Exception\AssetMismatch;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;

$registry = AssetRegistry::default();

$eur = Money::of('1250.50', 'EUR', $registry);
$tronUsdt = Money::of('10.000000', 'USDT@TRON', $registry);
$ethereumUsdt = Money::of('10.000000', 'USDT@ETHEREUM', $registry);
$eth = Money::ofMinor('1123456789123456789', 'ETH@ETHEREUM', $registry);

echo 'EUR amount: ' . $eur->toDecimalString() . PHP_EOL;
echo 'USDT on Tron: ' . $tronUsdt->asset()->id()->value() . PHP_EOL;
echo 'USDT on Ethereum: ' . $ethereumUsdt->asset()->id()->value() . PHP_EOL;
echo 'ETH from minor units: ' . $eth->toDecimalString() . PHP_EOL;

try {
    $unused = $tronUsdt->plus($ethereumUsdt);
} catch (AssetMismatch $exception) {
    echo 'Asset mismatch protected: yes' . PHP_EOL;
}
