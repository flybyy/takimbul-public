<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';

$root=dirname(__DIR__);
$check=file_get_contents($root.'/scripts/launch-check.php')?:'';
expect_true(str_contains($check,'Giriş sonrası kaldırılmış Auth çağrısı'),'Launch check removed Auth call regressionını kontrol etmeli.');
expect_true(str_contains($check,'Zorunlu DB tabloları'),'Launch check zorunlu veritabanı tablolarını kontrol etmeli.');
expect_true(str_contains($check,'Zorunlu DB kolonları'),'Launch check zorunlu veritabanı kolonlarını kontrol etmeli.');
expect_true(str_contains($check,"'admins'"),'Launch check admins tablosunu kontrol etmeli.');
expect_true(str_contains($check,"'player_availabilities'"),'Launch check v9 müsaitlik tablosunu kontrol etmeli.');
expect_true(str_contains($check,"'updated_by_admin_id'"),'Launch check v9.1 admin ayar kolonunu kontrol etmeli.');

echo "PASS v911_launch_preflight_test\n";
