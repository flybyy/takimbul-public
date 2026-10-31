<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$controller=$root.'/app/Controllers/IdentityAdminController.php';
$router=file_get_contents($root.'/public/index.php')?:'';
$header=file_get_contents($root.'/app/Views/layouts/admin-header.php')?:'';
$permissions=file_get_contents($root.'/app/Services/AdminPermission.php')?:'';

$fail=[];
$ok=function(bool $cond,string $msg) use (&$fail): void { if(!$cond)$fail[]=$msg; };

$ok(is_file($controller),'IdentityAdminController must exist');
if(is_file($controller)){
    $src=file_get_contents($controller)?:'';
    $ok(str_contains($src,"requirePermission('moderation.manage')")||str_contains($src,"gate('moderation.manage')"),'identity admin must require moderation.manage');
    $ok(str_contains($src,'RefereeProfile::adminList'),'admin list must include referee profiles');
    $ok(str_contains($src,'CoachProfile::adminList'),'admin list must include coach profiles');
    $ok(str_contains($src,'setAdminStatus'),'admin must manage profile moderation status');
    $ok(str_contains($src,'setLicenseStatus'),'admin must manage professional license status separately');
    $ok(str_contains($src,'AdminAuditLog::record'),'identity admin changes must be audited');
    $ok(str_contains($src,'Csrf::validate'),'identity admin mutations must validate CSRF');
}

foreach([
    '/admin/futbol-kimlikleri',
    '/admin/hakem/{id}',
    '/admin/hakem/{id}/durum',
    '/admin/hakem/{id}/lisans',
    '/admin/teknik-direktor/{id}',
    '/admin/teknik-direktor/{id}/durum',
    '/admin/teknik-direktor/{id}/lisans',
] as $route){$ok(str_contains($router,$route),'missing admin identity route: '.$route);}

$ok(str_contains($header,'/admin/futbol-kimlikleri'),'admin header must expose Football Identities moderation');
$ok(str_contains($permissions,"'authorized'=>")&&str_contains($permissions,"'moderation.manage'"),'authorized must retain moderation.manage');
$ok(!preg_match("/'writer'\s*=>\s*\[[^\]]*moderation\.manage/s",$permissions),'writer must not receive moderation.manage');

foreach(['app/Views/admin/football-identities.php','app/Views/admin/referee-profile.php','app/Views/admin/coach-profile.php'] as $view){$ok(is_file($root.'/'.$view),'missing admin identity view: '.$view);}

if($fail){fwrite(STDERR,"FAIL\n - ".implode("\n - ",$fail)."\n");exit(1);}echo "PASS v108_identity_admin_permissions_test\n";
