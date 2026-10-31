<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$migrationPath=$root.'/database/migrations/2026-09-05-v107-verification-roles-sponsored-news.sql';
expect_true(is_file($migrationPath),'V10.7 migration dosyası bulunmalı.');
$migration=is_file($migrationPath)?(string)file_get_contents($migrationPath):'';
$schema=(string)file_get_contents($root.'/database/schema.sql');
$repair=(string)file_get_contents($root.'/app/Core/SchemaRepair.php');
$launch=(string)file_get_contents($root.'/scripts/launch-check.php');
$smoke=(string)file_get_contents($root.'/scripts/production-smoke.php');
foreach(['role','public_slug','public_title','public_bio','avatar_path','instagram_url','x_url','public_profile_enabled'] as $column){
    expect_true(str_contains($migration,$column),'Migration admins.'.$column.' alanını içermeli.');
    expect_true(str_contains($schema,$column),'Schema admins.'.$column.' alanını içermeli.');
}
foreach(['profile_verified_at','profile_verified_by_admin_id'] as $column){
    expect_true(str_contains($migration,$column),'Migration users.'.$column.' alanını içermeli.');
    expect_true(str_contains($schema,$column),'Schema users.'.$column.' alanını içermeli.');
}
foreach(['news_sponsors','admin_audit_log'] as $table){
    expect_true(str_contains($migration,'CREATE TABLE IF NOT EXISTS '.$table),'Migration '.$table.' tablosunu oluşturmalı.');
    expect_true(str_contains($schema,'CREATE TABLE IF NOT EXISTS '.$table),'Schema '.$table.' tablosunu içermeli.');
    expect_true(str_contains($repair,"'{$table}'"),'SchemaRepair '.$table.' tablosunu izlemeli.');
    expect_true(str_contains($smoke,"'{$table}'"),'Production smoke '.$table.' tablosunu okumalı.');
}
foreach(['sponsor_id','sponsor_cta_text','sponsor_cta_url'] as $column){
    expect_true(str_contains($migration,$column),'Migration news.'.$column.' alanını içermeli.');
    expect_true(str_contains($schema,$column),'Schema news.'.$column.' alanını içermeli.');
}
expect_true(str_contains($migration,'@v107_admin_role_was_missing'),'Migration rol yükseltmesini yalnız kolon ilk kez eklenirken yapmalı.');
expect_true(str_contains($migration,"UPDATE admins SET role='super_admin'"),'Mevcut adminler ilk V10.7 geçişinde super_admin olmalı.');
expect_true(!str_contains($migration,"UPDATE admins SET role='super_admin' WHERE role IS NULL OR role='writer'"),'Migration tekrar çalışınca writer hesaplarını super_admin yapmamalı.');
expect_true(str_contains($repair,'adminRoleWasMissing'),'SchemaRepair rol kolonunun ilk kez eklenmesini izlemeli.');
expect_true(!str_contains($repair,"UPDATE admins SET role='super_admin' WHERE role IS NULL OR role='writer'"),'SchemaRepair tekrar çalışınca writer hesaplarını yükseltmemeli.');
expect_true(!preg_match('/\b(DROP|TRUNCATE)\b/i',$migration),'V10.7 migration destructive SQL içermemeli.');
expect_true(str_contains($launch,'V10.7 verification roles sponsored news migration'),'Launch check V10.7 migration dosyasını doğrulamalı.');
expect_true(str_contains($smoke,'INSERT INTO news_sponsors'),'Production smoke sponsor write-smoke yapmalı.');
expect_true(str_contains($smoke,'INSERT INTO admin_audit_log'),'Production smoke audit log write-smoke yapmalı.');
echo "PASS v107_schema_contract_test\n";
