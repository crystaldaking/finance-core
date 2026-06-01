<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Crystal\Finance\Core\Exception\AssetMismatch;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;

$registry = AssetRegistry::default();

$invoiceTotal = Money::of('1250.50', 'EUR', $registry);
$tronSettlement = Money::of('10.000000', 'USDT@TRON', $registry);
$ethereumSettlement = Money::of('10.000000', 'USDT@ETHEREUM', $registry);
$ethGasReserve = Money::ofMinor('1123456789123456789', 'ETH@ETHEREUM', $registry);

echo 'Invoice total: ' . $invoiceTotal->toDecimalString() . ' ' . $invoiceTotal->asset()->id()->value() . PHP_EOL;
echo 'Settlement rail A: ' . $tronSettlement->asset()->id()->value() . PHP_EOL;
echo 'Settlement rail B: ' . $ethereumSettlement->asset()->id()->value() . PHP_EOL;
echo 'ETH gas reserve: ' . $ethGasReserve->toDecimalString() . PHP_EOL;
echo 'ETH gas reserve minor units: ' . $ethGasReserve->toMinorUnitString() . PHP_EOL;

try {
    $unused = $tronSettlement->plus($ethereumSettlement);
} catch (AssetMismatch $exception) {
    echo 'Cross-network USDT addition blocked: yes' . PHP_EOL;
}
