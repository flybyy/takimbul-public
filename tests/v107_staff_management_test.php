<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$staffController=dirname(__DIR__).'/app/Controllers/StaffController.php';
expect_true(is_file($staffController),'StaffController bulunmalı.');
$src=(string)file_get_contents($staffController);
expect_true(str_contains($src,"requirePermission('staff.manage')"),'Staff mutations super_admin permission ile korunmalı.');
expect_true(str_contains($src,'canChangeRoleOrStatus'),'Son super admin guard controllerda kullanılmalı.');
expect_true(str_contains($src,'Admin::setStaffPassword'),'StaffController şifre hashlemeyi merkezi Admin modeline devretmeli.');
$admin=(string)file_get_contents(dirname(__DIR__).'/app/Models/Admin.php');
foreach(['allStaff','createStaff','updateStaff','setStaffPassword','validateStaffInput'] as $method) expect_true(str_contains($admin,$method),'Admin::'.$method.' bulunmalı.');
expect_true(str_contains($admin,'password_hash('),'Staff şifresi password_hash ile saklanmalı.');
$view=dirname(__DIR__).'/app/Views/admin/staff.php';expect_true(is_file($view),'Ekip & Yetkiler view bulunmalı.');expect_true(!str_contains((string)file_get_contents($view),'password_hash'),'Staff list password hash render etmemeli.');
$header=(string)file_get_contents(dirname(__DIR__).'/app/Views/layouts/admin-header.php');
expect_true(str_contains($header,"AdminAuth::can('staff.manage')"),'Ekip linki permission-aware olmalı.');
expect_true(str_contains($header,"AdminAuth::can('ads.manage')"),'Reklam linki permission-aware olmalı.');
expect_true(str_contains($header,"AdminAuth::can('moderation.manage')"),'Moderasyon linki permission-aware olmalı.');
$routes=(string)file_get_contents(dirname(__DIR__).'/public/index.php');expect_true(str_contains($routes,"/admin/ekip"),'Ekip route bulunmalı.');
echo "PASS v107_staff_management_test\n";
