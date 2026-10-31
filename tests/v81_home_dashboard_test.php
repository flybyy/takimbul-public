<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$c=file_get_contents($root.'/app/Controllers/HomeController.php')?:'';
$v=file_get_contents($root.'/app/Views/home.php')?:'';
foreach(['Career::summary','PlayerProfile::find','User::profileIdentity','adContext','currentStats','currentProfile'] as $n)$check(str_contains($c,$n),'HomeController eksik: '.$n);
$check(!str_contains($v,'\$user?\'0\':\'124\''),'Giriş yapan kullanıcı için hard-coded 0 maç hâlâ var');
$check(str_contains($v,"currentStats['matches']"),'Gerçek maç sayısı home view içinde kullanılmıyor');
$check(str_contains($v,"currentStats['season_rating']"),'Sezon puanı home view içinde kullanılmıyor');
$check(str_contains($v,'avatar_path'),'Home kariyer kartında avatar kullanılmıyor');
$check(substr_count($v,'AdRenderer::slot(\'home_feed\',$adContext)')===1,'home_feed reklamı adContext ile çağrılmıyor');
$check(substr_count($v,'AdRenderer::slot(\'home_sidebar\',$adContext)')===1,'home_sidebar reklamı adContext ile çağrılmıyor');
$check(substr_count($v,'AdRenderer::slot(\'mobile_feed\',$adContext)')===1,'mobile_feed reklamı adContext ile çağrılmıyor');
if($fail){fwrite(STDERR,"FAIL v81_home_dashboard_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v81_home_dashboard_test\n";
