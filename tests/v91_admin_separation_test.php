<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';

use App\Core\AdminAuth;
use App\Models\Admin;

expect_true(class_exists(Admin::class),'Separate Admin model must exist.');
expect_true(class_exists(AdminAuth::class),'Separate AdminAuth core must exist.');
expect_true(method_exists(AdminAuth::class,'requireLogin'),'AdminAuth::requireLogin must exist.');
expect_true(method_exists(Admin::class,'findByIdentifier'),'Admin::findByIdentifier must exist.');
expect_true(method_exists(Admin::class,'identifierReserved'),'Admin::identifierReserved must protect admin identifiers from public accounts.');
$userModel=file_get_contents(dirname(__DIR__).'/app/Models/User.php') ?: '';
expect_true(str_contains($userModel,'Admin::identifierReserved'),'Public user validation must reserve admin email/username identifiers.');

$schema=file_get_contents(dirname(__DIR__).'/database/schema.sql') ?: '';
expect_true(str_contains($schema,'CREATE TABLE IF NOT EXISTS admins'),'Fresh schema must contain separate admins table.');
expect_true(!str_contains($schema,"INSERT INTO users (email,password_hash,display_name,username,province,district,role,status,email_verified_at)\nVALUES ('admin@takimbul.com.tr'"),'Fresh schema must not seed admin inside users.');
expect_true(str_contains($schema,"INSERT INTO admins"),'Fresh schema must seed separate admin identity.');

$index=file_get_contents(dirname(__DIR__).'/public/index.php') ?: '';
expect_true(str_contains($index,"'/admin/giris'"),'Admin login route missing.');
expect_true(str_contains($index,"'/admin/cikis'"),'Admin logout route missing.');

$adminController=file_get_contents(dirname(__DIR__).'/app/Controllers/AdminController.php') ?: '';
expect_true(str_contains($adminController,'AdminAuth::requireLogin'),'Admin controller must use separate AdminAuth gate.');
expect_true(!str_contains($adminController,'Auth::requireAdmin'),'Admin controller must not use user auth admin role.');

$adController=file_get_contents(dirname(__DIR__).'/app/Controllers/AdController.php') ?: '';
expect_true(str_contains($adController,"AdminAuth::requirePermission('ads.manage')"),'Ad controller must use separate AdminAuth authorization gate.');

$migration=dirname(__DIR__).'/database/migrations/2026-09-01-v91-home-ads-admin.sql';
expect_true(is_file($migration),'v9.1 migration missing.');
$migrationText=is_file($migration)?(file_get_contents($migration)?:''):'';
expect_true(str_contains($migrationText,'CREATE TABLE IF NOT EXISTS admins'),'v9.1 migration must create admins table.');
expect_true(str_contains($migrationText,"status='deleted'") && str_contains($migrationText,"legacy-admin-"),'v9.1 migration must disable/tombstone the legacy admin user identity.');

echo "PASS v91_admin_separation_test\n";
