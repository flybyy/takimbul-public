<?php
require __DIR__.'/bootstrap.php';
$css=(string)file_get_contents(__DIR__.'/../public/assets/css/app.css');
expect_true(str_contains($css,'svg[viewBox="0 0 24 24"]'),'Global TakımBul inline icon contract must exist.');
expect_true(str_contains($css,'width:1.15em;height:1.15em'),'Unstyled inline icons must have bounded dimensions.');
echo "PASS v106_icon_contract_test\n";
