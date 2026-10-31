<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Services\AdminPermission;
$yes=[
 'super_admin'=>['dashboard','news.create','news.edit_any','news.edit_own','sponsors.manage','players.verify','moderation.manage','ads.manage','settings.manage','staff.manage','audit.view'],
 'authorized'=>['dashboard','news.create','news.edit_any','news.edit_own','sponsors.manage','players.verify','moderation.manage','audit.view'],
 'writer'=>['dashboard.editorial','news.create','news.edit_own','news.sponsor_attach'],
];
foreach($yes as $role=>$permissions){foreach($permissions as $permission){expect_true(AdminPermission::can($role,$permission),$role.' '.$permission.' yetkisine sahip olmalı.');}}
foreach(['ads.manage','settings.manage','staff.manage'] as $p) expect_true(!AdminPermission::can('authorized',$p),'authorized '.$p.' yetkisine sahip olmamalı.');
foreach(['news.edit_any','sponsors.manage','players.verify','moderation.manage','audit.view','staff.manage'] as $p) expect_true(!AdminPermission::can('writer',$p),'writer '.$p.' yetkisine sahip olmamalı.');
expect_true(!AdminPermission::can('writer','totally.unknown'),'Bilinmeyen permission deny-by-default olmalı.');
expect_true(!AdminPermission::can('hacker','news.create'),'Bilinmeyen rol deny-by-default olmalı.');
$adminAuth=(string)file_get_contents(dirname(__DIR__).'/app/Core/AdminAuth.php');
expect_true(str_contains($adminAuth,'function role('),'AdminAuth::role bulunmalı.');
expect_true(str_contains($adminAuth,'function can('),'AdminAuth::can bulunmalı.');
expect_true(str_contains($adminAuth,'function requirePermission('),'AdminAuth::requirePermission bulunmalı.');
$admin=(string)file_get_contents(dirname(__DIR__).'/app/Models/Admin.php');
expect_true(str_contains($admin,'countActiveSuperAdmins'),'Son super admin koruması için countActiveSuperAdmins olmalı.');
expect_true(str_contains($admin,'canChangeRoleOrStatus'),'Son super admin koruması server-side olmalı.');
echo "PASS v107_permissions_test\n";
