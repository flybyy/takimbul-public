<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$check=file_get_contents($root.'/scripts/launch-check.php')?:'';
expect_true(str_contains($check,"'welcome_ad_enabled'"),'Launch-check must inspect welcome_ad_enabled setting.');
expect_true(str_contains($check,"placement IN (\"home_top\",\"welcome_takeover\")"),'Launch-check must inspect first-screen ad campaign readiness.');
expect_true(str_contains($check,'İlk ekran reklam kampanyası'),'Launch-check must report first-screen ad campaign readiness.');
echo "PASS v10_launch_ad_readiness_test\n";
