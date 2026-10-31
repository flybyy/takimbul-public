<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\Ad;

$valid=[
    'type'=>'manual','advertiser_name'=>'Faralli Coffee','title'=>'Maç sonrası kahveni kap','body'=>'TakımBul üyelerine özel fırsat.',
    'target_url'=>'https://example.com/kampanya','placement'=>'home_feed','device_target'=>'all','province'=>'Antalya','district'=>'Alanya',
    'starts_at'=>'2026-08-31 10:00:00','ends_at'=>'2026-09-30 23:59:59','status'=>'active','image_url'=>'/uploads/ads/faralli.webp','external_code'=>''
];
expect_same([],Ad::validateInput($valid),'Valid manual ad must pass validation.');
$invalid=$valid;
$invalid['target_url']='javascript:alert(1)';
$invalid['placement']='evil_slot';
$invalid['device_target']='watch';
$invalid['ends_at']='2026-08-01 00:00:00';
$errors=Ad::validateInput($invalid);
expect_true(isset($errors['target_url']),'Unsafe target URL must be rejected.');
expect_true(isset($errors['placement']),'Unknown placement must be rejected.');
expect_true(isset($errors['device_target']),'Unknown device target must be rejected.');
expect_true(isset($errors['ends_at']),'End before start must be rejected.');
$external=$valid;
$external['type']='external_code';
$external['target_url']='';
$external['image_url']='';
$external['external_code']='<ins class="adsbygoogle"></ins><script>adsbygoogle.push({});</script>';
expect_same([],Ad::validateInput($external),'External-code ad should allow empty manual URL when code exists.');
$external['external_code']='';
expect_true(isset(Ad::validateInput($external)['external_code']),'External-code ad requires code.');
expect_same('https://example.com/a',Ad::safeTargetUrl('https://example.com/a'),'HTTPS target must be accepted.');
expect_same(null,Ad::safeTargetUrl('//evil.test/a'),'Protocol-relative target must be rejected.');
expect_same(null,Ad::safeTargetUrl('javascript:alert(1)'),'Javascript target must be rejected.');
expect_same(3.25,Ad::ctr(13,400),'CTR should be clicks/impressions percentage rounded to two decimals.');
expect_same(0.0,Ad::ctr(5,0),'CTR with zero impressions must be zero.');

$live=['status'=>'active','starts_at'=>'2026-08-31 00:00:00','ends_at'=>'2026-09-30 23:59:59'];
expect_true(Ad::isLive($live,strtotime('2026-09-01 12:00:00')),'Active ad inside schedule must be live.');
$live['status']='paused';
expect_true(!Ad::isLive($live,strtotime('2026-09-01 12:00:00')),'Paused ad must not be publicly clickable.');
$live['status']='active';$live['ends_at']='2026-08-31 23:00:00';
expect_true(!Ad::isLive($live,strtotime('2026-09-01 12:00:00')),'Expired ad must not be publicly clickable.');
echo "PASS ad_rules_test\n";
