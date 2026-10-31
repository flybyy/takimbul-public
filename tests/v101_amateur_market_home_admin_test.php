<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$home=file_get_contents($root.'/app/Views/home.php')?:'';
$adminHeader=file_get_contents($root.'/app/Views/layouts/admin-header.php')?:'';
$adminController=file_get_contents($root.'/app/Controllers/AdminController.php')?:'';
$launch=file_get_contents($root.'/scripts/launch-check.php')?:'';
expect_true(str_contains($home,'Amatör futbolcu musun? Kendini göster, takımını bul.'),'Homepage amateur profile/showcase section missing.');
expect_true(str_contains($adminHeader,'Amatör'),'Admin amateur nav missing.');
expect_true(str_contains($adminController,'amateurAds'),'Admin amateur listing method missing.');
expect_true(str_contains($adminController,'amateurAdShow'),'Admin amateur detail method missing.');
foreach(['amateur_player_ads','amateur_player_career_entries','amateur_player_ad_events','amateur_player_favorites'] as $table) expect_true(str_contains($launch,$table),'Launch-check missing '.$table);
echo "PASS v101_amateur_market_home_admin_test\n";
