<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\Ad;

$now=strtotime('2026-09-01 12:00:00');
$ad=['status'=>'active','placement'=>'home_feed','province'=>'İSTANBUL','district'=>'Kadıköy','device_target'=>'all','starts_at'=>'2026-09-01 00:00:00','ends_at'=>'2026-09-30 23:59:59'];
expect_true(Ad::eligibleForContext($ad,'home_feed','istanbul','kadikoy','desktop',$now),'Turkish character folding should match location targets.');

$model=file_get_contents(dirname(__DIR__).'/app/Models/Ad.php') ?: '';
$pickStart=strpos($model,'public static function pickForContext');
$pickBody=$pickStart===false?'':substr($model,$pickStart,1800);
expect_true(!str_contains($pickBody,'LOWER(province)=LOWER(:province)'),'Ad delivery SQL must not reject locale-normalizable provinces.');
expect_true(!str_contains($pickBody,'LOWER(district)=LOWER(:district)'),'Ad delivery SQL must not reject locale-normalizable districts.');
expect_true(str_contains($pickBody,'eligibleForContext'),'Ad candidates must be normalized/filter checked in PHP.');
expect_true(str_contains($pickBody,'locationSpecificity'),'Eligible local campaigns must be preferred over generic campaigns without breaking fallback.');

$adminView=file_get_contents(dirname(__DIR__).'/app/Views/admin/ads/index.php') ?: '';
expect_true(str_contains($adminView,'name="province"') && str_contains($adminView,'name="district"'),'Admin ad center must allow manual location delivery testing for isolated admins.');
echo "PASS v91_ad_delivery_fix_test\n";
