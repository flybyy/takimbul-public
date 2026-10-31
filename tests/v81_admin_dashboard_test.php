<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\User;
$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$check(method_exists(User::class,'adminStats'),'User::adminStats eksik');
$check(method_exists(User::class,'adminSearch'),'User::adminSearch eksik');
$check(method_exists(User::class,'recentForAdmin'),'User::recentForAdmin eksik');
$userSource=file_get_contents(dirname(__DIR__).'/app/Models/User.php')?:'';
$setPos=strpos($userSource,'function setStatus');$setBlock=$setPos!==false?substr($userSource,$setPos,700):'';
$check(str_contains($setBlock,'status IN'), 'Silinmiş hesapların admin durum aksiyonuyla yeniden açılması engellenmiyor');
$r=file_get_contents(dirname(__DIR__).'/public/index.php')?:'';$check(str_contains($r,"/admin/kullanicilar"),'Admin kullanıcılar route eksik');
$c=file_get_contents(dirname(__DIR__).'/app/Controllers/AdminController.php')?:'';
foreach(['adminStats','recentForAdmin','function users','adminSearch'] as $n)$check(str_contains($c,$n),'AdminController eksik: '.$n);
$v=file_get_contents(dirname(__DIR__).'/app/Views/admin/index.php')?:'';
foreach(['Toplam Kullanıcı','Aktif Takım','Açık İlan','Reklam','Son Kayıtlar'] as $n)$check(str_contains($v,$n),'Admin dashboard eksik: '.$n);
$users=dirname(__DIR__).'/app/Views/admin/users.php';$check(is_file($users),'Admin users view eksik');
if(is_file($users)){$u=file_get_contents($users)?:'';foreach(['Kullanıcı Yönetimi','name="q"','Askıya Al','Aktifleştir'] as $n)$check(str_contains($u,$n),'Users view eksik: '.$n);}
if($fail){fwrite(STDERR,"FAIL v81_admin_dashboard_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v81_admin_dashboard_test\n";
