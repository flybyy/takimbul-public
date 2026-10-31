<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';

$controller = (string)file_get_contents(__DIR__.'/../app/Controllers/MarketController.php');
expect_true(str_contains($controller, "'loadError'"), 'Market listings must expose a loadError state instead of hard-failing when data cannot be loaded.');
expect_true(str_contains($controller, 'Logger::exception'), 'Market listing load failures must be logged.');

$views = [
    __DIR__.'/../app/Views/market/players.php',
    __DIR__.'/../app/Views/market/opponents.php',
];
foreach ($views as $view) {
    $source = (string)file_get_contents($view);
    expect_true(!str_contains($source, '</select>">'), basename($view).' contains malformed select markup.');
    expect_true(str_contains($source, '$loadError'), basename($view).' must render a distinct data-load failure state.');
}

echo "PASS v104_market_resilience_contract_test\n";
