<?php
declare(strict_types=1);require __DIR__.'/bootstrap.php';
use App\Models\PlayerAvailability;
expect_true(class_exists(PlayerAvailability::class),'PlayerAvailability model must exist');
$errors=PlayerAvailability::validateInput(['starts_at'=>'2026-09-01T20:00','ends_at'=>'2026-09-01T23:00','province'=>'Antalya','district'=>'Alanya','position'=>'Orta Saha','radius_km'=>'15'],strtotime('2026-09-01 12:00:00'));
expect_same([], $errors, 'valid availability should pass');
$bad=PlayerAvailability::validateInput(['starts_at'=>'2026-09-01T10:00','ends_at'=>'2026-09-01T09:00','province'=>'','district'=>'','position'=>'Masör','radius_km'=>'500'],strtotime('2026-09-01 12:00:00'));
expect_true(isset($bad['starts_at']) && isset($bad['ends_at']) && isset($bad['province']) && isset($bad['position']) && isset($bad['radius_km']),'invalid availability fields should fail');
expect_true(PlayerAvailability::isActiveWindow('2026-09-01 20:00:00','2026-09-01 23:00:00',strtotime('2026-09-01 21:00:00')),'window should be active inside range');
expect_true(!PlayerAvailability::isActiveWindow('2026-09-01 20:00:00','2026-09-01 23:00:00',strtotime('2026-09-02 00:00:00')),'window should be inactive after end');
echo "PASS availability_rules_test\n";
