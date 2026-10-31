<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$schema=(string)file_get_contents($root.'/database/schema.sql');
$repair=(string)file_get_contents($root.'/app/Core/SchemaRepair.php');
$launch=(string)file_get_contents($root.'/scripts/launch-check.php');
$index=(string)file_get_contents($root.'/public/index.php');
$smoke=(string)file_get_contents($root.'/scripts/production-smoke.php');
expect_true(str_contains($schema,'CREATE TABLE IF NOT EXISTS news'),'Ana schema news tablosunu içermeli.');
expect_true(str_contains($repair,"'news'"),'Production repair news tablosunu kritik şema olarak izlemeli.');
expect_true(str_contains($launch,'V10.4 Türkiye konum verisi'),'Launch check Türkiye konum verisini doğrulamalı.');
expect_true(str_contains($launch,'V10.4 haber migration'),'Launch check news migration dosyasını doğrulamalı.');
expect_true(str_contains($launch,"'news'"),'Launch check canlı DB news tablosunu doğrulamalı.');
expect_true(str_contains($index,"'/haberler'"),'Haberler route production indexte bulunmalı.');
expect_true(str_contains($smoke,"'news'"),'Production smoke news tablosunu read-smoke içinde doğrulamalı.');
expect_true(str_contains($smoke,'INSERT INTO news'),'Production smoke transactional news yazımını doğrulamalı.');
echo "PASS v104_launch_contract_test\n";
