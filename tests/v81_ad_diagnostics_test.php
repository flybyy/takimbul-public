<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\Ad;use App\Core\AdRenderer;
$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$check(method_exists(Ad::class,'diagnose'),'Ad::diagnose eksik');
$check(method_exists(AdRenderer::class,'preview'),'AdRenderer::preview eksik');
$base=['id'=>5,'type'=>'manual','advertiser_name'=>'Test','title'=>'Test Reklam','body'=>'Açıklama','target_url'=>'https://example.com','image_url'=>null,'external_code'=>null,'placement'=>'home_feed','device_target'=>'all','province'=>null,'district'=>null,'status'=>'active','starts_at'=>'2026-08-31 10:00:00','ends_at'=>'2026-09-30 10:00:00'];
if(method_exists(Ad::class,'diagnose')){
 $d=Ad::diagnose($base,['placement'=>'home_feed','device'=>'desktop','province'=>'','district'=>'','now'=>strtotime('2026-09-01 12:00:00')]);
 $check(($d['code']??'')==='live','Canlı reklam live teşhisi vermedi');
 $paused=$base;$paused['status']='paused';$d=Ad::diagnose($paused,['placement'=>'home_feed','device'=>'desktop','now'=>strtotime('2026-09-01')]);$check(($d['code']??'')==='paused','Duraklatılmış reklam paused değil');
 $geo=$base;$geo['province']='Antalya';$geo['district']='Alanya';$d=Ad::diagnose($geo,['placement'=>'home_feed','device'=>'desktop','province'=>'İstanbul','district'=>'Kadıköy','now'=>strtotime('2026-09-01')]);$check(($d['code']??'')==='province_mismatch','İl uyuşmazlığı teşhis edilmedi');
 $future=$base;$future['starts_at']='2026-09-10 00:00:00';$d=Ad::diagnose($future,['placement'=>'home_feed','device'=>'desktop','now'=>strtotime('2026-09-01')]);$check(($d['code']??'')==='scheduled','Gelecek kampanya scheduled değil');
}
if(method_exists(AdRenderer::class,'preview')){
 $html=AdRenderer::preview($base);$check(str_contains($html,'Sponsorlu'),'Önizleme sponsorlu kart üretmiyor');$check(str_contains($html,'Test Reklam'),'Önizleme reklam başlığını içermiyor');
}
$routes=file_get_contents(dirname(__DIR__).'/public/index.php')?:'';$check(str_contains($routes,"/admin/reklamlar/{id}/onizle"),'Reklam önizleme route eksik');
$controller=file_get_contents(dirname(__DIR__).'/app/Controllers/AdController.php')?:'';$check(str_contains($controller,'function preview'),'AdController preview action eksik');
$check(str_contains($controller,'deliveryContext'),'AdController admin test context sağlamıyor');
$check(!str_contains($controller,'profileIdentity'),'Ayrı admin hesabı reklam teşhisi için oyuncu profiline bağlı olmamalı');
$check(str_contains($controller,"['province']") && str_contains($controller,"['district']"),'Admin reklam teşhisi manuel konum context desteklemiyor');
$view=file_get_contents(dirname(__DIR__).'/app/Views/admin/ads/index.php')?:'';$check(str_contains($view,'Teslimat'),'Admin reklam listesinde teslimat teşhisi yok');$check(str_contains($view,'Önizle'),'Admin reklam listesinde önizleme aksiyonu yok');
$core=file_get_contents(dirname(__DIR__).'/app/Core/AdRenderer.php')?:'';
if(preg_match('/function\s+preview\b.*?\n\s*\}/s',$core,$m))$check(!str_contains($m[0],'recordImpression'),'Preview impression sayacını artırıyor');
if($fail){fwrite(STDERR,"FAIL v81_ad_diagnostics_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v81_ad_diagnostics_test\n";
