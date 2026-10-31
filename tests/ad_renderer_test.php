<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Core\AdRenderer;
$manual=['id'=>7,'type'=>'manual','advertiser_name'=>'Brand <x>','title'=>'Golünü kutla','body'=>'Özel fırsat','image_url'=>'https://cdn.example.com/ad.webp','target_url'=>'https://example.com','placement'=>'home_feed'];
$html=AdRenderer::renderAd($manual);
expect_true(str_contains($html,'Sponsorlu'),'Manual ad must visibly say Sponsorlu.');
expect_true(str_contains($html,'/reklam/tikla/7'),'Manual ad CTA must use tracked TakımBul click URL.');
expect_true(!str_contains($html,'Brand <x>'),'Manual ad content must be HTML escaped.');
expect_true(str_contains($html,'Brand &lt;x&gt;'),'Escaped advertiser must render.');
$external=['id'=>9,'type'=>'external_code','advertiser_name'=>'Google AdSense','title'=>'AdSense','external_code'=>'<ins class="adsbygoogle"></ins>','placement'=>'home_sidebar'];
$out=AdRenderer::renderAd($external);
expect_true(str_contains($out,'ad-external-frame'),'External ad must use constrained external wrapper.');
expect_true(str_contains($out,'<ins class="adsbygoogle"></ins>'),'Trusted admin external code must render raw.');
echo "PASS ad_renderer_test\n";
