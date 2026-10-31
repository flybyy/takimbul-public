<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\Ad;use App\Core\Seo;
foreach(['amateur_list_top','amateur_list_inline','amateur_detail_inline'] as $slot) expect_true(in_array($slot,Ad::PLACEMENTS,true),'Ad placement missing: '.$slot);
$seo=Seo::forRequest('/amator-futbolcular',[],[]);
expect_true(str_contains($seo['title'],'Amatör'),'Amateur list SEO title missing.');
expect_same('index,follow',$seo['robots'],'Amateur list should be indexable.');
$filtered=Seo::forRequest('/amator-futbolcular',['province'=>'Antalya'],[]);
expect_same('noindex,follow',$filtered['robots'],'Filtered amateur listing should noindex follow.');
$detail=Seo::forRequest('/amator-futbolcu/test-oyuncu',[],['amateurAd'=>['display_name'=>'Test Oyuncu','primary_position'=>'Forvet','province'=>'Antalya','district'=>'Alanya']]);
expect_true(str_contains($detail['title'],'Test Oyuncu'),'Amateur detail SEO should contain player name.');
$seoController=file_get_contents(dirname(__DIR__).'/app/Controllers/SeoController.php')?:'';
expect_true(str_contains($seoController,"'/amator-futbolcular'"),'Sitemap static amateur URL missing.');
expect_true(str_contains($seoController,'amateur_player_ads'),'Sitemap amateur ad query missing.');
echo "PASS v101_amateur_market_ads_seo_test\n";
