<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\Ad;
$root=dirname(__DIR__);
expect_true(in_array('home_top',Ad::PLACEMENTS,true),'home_top ad placement missing.');
expect_true(in_array('welcome_takeover',Ad::PLACEMENTS,true),'welcome_takeover ad placement missing.');
$schema=file_get_contents($root.'/database/schema.sql')?:'';
expect_true(str_contains($schema,"'home_top'"),'Fresh schema ad enum must include home_top.');
expect_true(str_contains($schema,"'welcome_takeover'"),'Fresh schema ad enum must include welcome_takeover.');
expect_true(str_contains($schema,"'welcome_ad_enabled'"),'Fresh schema must seed welcome_ad_enabled setting.');
$home=file_get_contents($root.'/app/Views/home.php')?:'';
expect_true(str_contains($home,"AdRenderer::slot('home_top'"),'Home must render first-viewport home_top ad.');
expect_true(str_contains($home,'welcome-ad-takeover'),'Home must contain welcome takeover surface.');
$form=file_get_contents($root.'/app/Views/admin/ads/form.php')?:'';
expect_true(str_contains($form,'home_top'),'Admin ad form missing home_top.');
expect_true(str_contains($form,'welcome_takeover'),'Admin ad form missing welcome_takeover.');
echo "PASS v10_ad_premium_test\n";
