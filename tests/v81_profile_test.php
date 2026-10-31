<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\User;
use App\Models\PlayerProfile;
$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$check(method_exists(User::class,'profileIdentity'),'User::profileIdentity eksik');
$check(method_exists(User::class,'validateProfileIdentity'),'User::validateProfileIdentity eksik');
$check(method_exists(User::class,'updateProfileIdentity'),'User::updateProfileIdentity eksik');
$check(method_exists(PlayerProfile::class,'validateAvatarFile'),'Avatar doğrulama metodu eksik');
$check(method_exists(PlayerProfile::class,'storeAvatar'),'Avatar kayıt metodu eksik');
$view=file_get_contents(dirname(__DIR__).'/app/Views/profile/edit.php')?:'';
foreach(['name="display_name"','name="username"','name="province"','name="district"','name="avatar"','enctype="multipart/form-data"'] as $needle)$check(str_contains($view,$needle),'Profil formunda eksik: '.$needle);
$controller=file_get_contents(dirname(__DIR__).'/app/Controllers/ProfileController.php')?:'';
$check(str_contains($controller,'validateProfileIdentity'),'Profil controller kimlik doğrulamasını kullanmıyor');
$check(str_contains($controller,'storeAvatar'),'Profil controller avatarı saklamıyor');
$career=file_get_contents(dirname(__DIR__).'/app/Views/career/show.php')?:'';
$check(str_contains($career,'avatar_path'),'Kariyer görünümünde avatar_path kullanılmıyor');
if(method_exists(User::class,'validateUsername')){
    $check(User::validateUsername('murat.dincer')===null,'noktalı geçerli username kabul edilmeli');
}
// 1x1 PNG actual MIME contract
$tmp=tempnam(sys_get_temp_dir(),'tb-avatar-');
file_put_contents($tmp,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2F5sAAAAASUVORK5CYII='));
if(method_exists(PlayerProfile::class,'validateAvatarFile')){
    $err=PlayerProfile::validateAvatarFile(['error'=>UPLOAD_ERR_OK,'size'=>filesize($tmp),'tmp_name'=>$tmp,'name'=>'avatar.png']);
    $check($err===null,'Geçerli PNG avatar reddedildi: '.(string)$err);
    $err=PlayerProfile::validateAvatarFile(['error'=>UPLOAD_ERR_OK,'size'=>20,'tmp_name'=>$tmp,'name'=>'avatar.php']);
    $check($err!==null,'PHP uzantılı avatar reddedilmedi');
}
@unlink($tmp);
if($fail){fwrite(STDERR,"FAIL v81_profile_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v81_profile_test\n";
