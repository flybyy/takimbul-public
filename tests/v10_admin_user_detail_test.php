<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\User;
$root=dirname(__DIR__);
$index=file_get_contents($root.'/public/index.php')?:'';
expect_true(str_contains($index,"'/admin/kullanici/{id}'"),'Admin user detail route missing.');
expect_true(str_contains($index,"'/admin/kullanici/{id}/iletisim'"),'Admin contact update route missing.');
expect_true(str_contains($index,"'/admin/kullanici/{id}/not'"),'Admin note route missing.');
expect_true(str_contains($index,"'/admin/kullanici/{id}/sifre-sifirla'"),'Admin password reset route missing.');
expect_true(method_exists(User::class,'adminDetail'),'User::adminDetail missing.');
expect_true(method_exists(User::class,'adminUpdateContact'),'User::adminUpdateContact missing.');
expect_true(is_file($root.'/app/Views/admin/user-detail.php'),'Admin user detail view missing.');
$schema=file_get_contents($root.'/database/schema.sql')?:'';
expect_true(str_contains($schema,'CREATE TABLE IF NOT EXISTS admin_user_notes'),'admin_user_notes table missing in fresh schema.');
echo "PASS v10_admin_user_detail_test\n";
