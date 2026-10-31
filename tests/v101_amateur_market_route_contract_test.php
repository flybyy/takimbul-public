<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);$index=file_get_contents($root.'/public/index.php')?:'';
foreach([
 "'/amator-futbolcular'","'/amator-ilanim'","'/amator-ilanim/yayinla'","'/amator-ilanim/duraklat'",
 "'/amator-ilanim/yenile'","'/amator-ilanim/takimimi-buldum'","'/amator-futbolcu/{slug}'",
 "'/amator-futbolcu/{id}/favori'","'/amator-futbolcu/{id}/davet'",
 "'/admin/amator-ilanlar'","'/admin/amator-ilan/{id}'","'/admin/amator-ilan/{id}/durum'"
] as $route) expect_true(str_contains($index,$route),'Route missing: '.$route);
foreach(['app/Controllers/AmateurMarketController.php','app/Models/AmateurPlayerAd.php','app/Models/AmateurCareerEntry.php','app/Views/amateur-market/index.php','app/Views/amateur-market/form.php','app/Views/amateur-market/show.php','app/Views/admin/amateur-ads.php','app/Views/admin/amateur-ad-show.php'] as $file) expect_true(is_file($root.'/'.$file),'Missing file '.$file);
echo "PASS v101_amateur_market_route_contract_test\n";
