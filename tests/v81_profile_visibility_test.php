<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$user=file_get_contents($root.'/app/Models/User.php')?:'';
$check(str_contains($user,'u.username'),'publicPlayers kullanıcı adını seçmiyor');
$check(str_contains($user,'p.avatar_path'),'publicPlayers avatar_path seçmiyor');
$findStart=strpos($user,'function findPublic');$findBlock=$findStart!==false?substr($user,$findStart,800):'';$check(str_contains($findBlock,'username'),'findPublic username döndürmüyor');
$controller=file_get_contents($root.'/app/Controllers/CareerController.php')?:'';
$check(str_contains($controller,'$visibility===\'members\''),'Members görünürlük kuralı eksik');
$check(str_contains($controller,'Auth::isAdmin') || str_contains($controller,'viewerId'),'Kariyer görünürlük kontrolü viewer kullanmıyor');
$view=file_get_contents($root.'/app/Views/career/show.php')?:'';
$check(str_contains($view,'username'),'Kariyer sayfasında kullanıcı adı görünmüyor');
$check(str_contains($view,'/profil/duzenle'),'Kendi kariyerinde profil düzenle aksiyonu yok');
if($fail){fwrite(STDERR,"FAIL v81_profile_visibility_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v81_profile_visibility_test\n";
