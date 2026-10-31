<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';

use App\Models\User;

$failures=[];
$check=static function(bool $ok,string $message) use (&$failures): void { if(!$ok)$failures[]=$message; };

$check(method_exists(User::class,'findByIdentifier'),'User::findByIdentifier eksik.');
$check(method_exists(User::class,'validateUsername'),'User::validateUsername eksik.');
if(method_exists(User::class,'validateUsername')){
    $check(User::validateUsername('veribayt')===null,'veribayt geçerli kullanıcı adı olmalı.');
    $check(User::validateUsername('ab')!==null,'2 karakter kullanıcı adı reddedilmeli.');
    $check(User::validateUsername('.abc')!==null,'nokta ile başlayan kullanıcı adı reddedilmeli.');
    $check(User::validateUsername('abc test')!==null,'boşluklu kullanıcı adı reddedilmeli.');
}

$login=file_get_contents(dirname(__DIR__).'/app/Views/auth/login.php') ?: '';
$controller=file_get_contents(dirname(__DIR__).'/app/Controllers/AuthController.php') ?: '';
$check(str_contains($login,'name="identifier"'),'Giriş formunda identifier alanı yok.');
$check(!str_contains($login,'name="email"'),'Giriş formu hâlâ sadece email alanını kullanıyor.');
$check(str_contains($controller,'findByIdentifier'),'AuthController kullanıcı adı/e-posta ortak lookup kullanmıyor.');

$migration=dirname(__DIR__).'/database/migrations/2026-08-31-v81-professional-stabilization.sql';
$check(is_file($migration),'v8.1 migration eksik.');
$schema=file_get_contents(dirname(__DIR__).'/database/schema.sql') ?: '';
$check(str_contains($schema,"'veribayt'"),'Fresh schema admin username seed içermiyor.');
$check(str_contains($schema,"'admin@takimbul.com.tr'"),'Fresh schema admin email seed içermiyor.');
$check(is_file(dirname(__DIR__).'/scripts/admin-bootstrap.php'),'Admin bootstrap CLI scripti eksik.');
$bootstrapText=is_file(dirname(__DIR__).'/scripts/admin-bootstrap.php')?(file_get_contents(dirname(__DIR__).'/scripts/admin-bootstrap.php')?:''):'';
$check(!str_contains($bootstrapText,'AppConfig::load'),'Admin bootstrap olmayan AppConfig::load metodunu çağırıyor.');

$forbidden=(string)(getenv('TAKIMBUL_ADMIN_FORBIDDEN_PLAINTEXT') ?: '');
if($forbidden!==''){
    foreach(['app','database','scripts','config','public'] as $dir){
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__).'/'.$dir,FilesystemIterator::SKIP_DOTS));
        foreach($it as $file){
            if(!$file->isFile())continue;
            $text=file_get_contents($file->getPathname()) ?: '';
            $check(!str_contains($text,$forbidden),'Admin parolası plaintext kaynakta bulundu: '.$file->getPathname());
        }
    }
}

if($failures){fwrite(STDERR,"FAIL v81_admin_auth_test\n- ".implode("\n- ",$failures)."\n");exit(1);} echo "PASS v81_admin_auth_test\n";
