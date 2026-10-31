<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';

$check=file_get_contents(dirname(__DIR__).'/scripts/launch-check.php')?:'';
foreach(['public/index.php','public/assets/css/app.css','public/assets/js/app.js','app/Views/auth/login.php','app/Views/auth/register.php','app/Views/errors/403.php','app/Views/errors/404.php','app/Views/errors/419.php','app/Views/errors/500.php'] as $asset){
    expect_true(str_contains($check,$asset),"Launch-check dosya bütünlüğünü kontrol etmeli: {$asset}");
}
expect_true(str_contains($check,'CSS dosyası'),'Launch-check CSS varlığını görünür isimle raporlamalı.');
expect_true(str_contains($check,'JS dosyası'),'Launch-check JS varlığını görünür isimle raporlamalı.');
expect_true(str_contains($check,'Kritik kullanıcı ekranları'),'Launch-check giriş/kayıt/hata view bütünlüğünü kontrol etmeli.');

echo "PASS v912_launch_assets_contract_test\n";
