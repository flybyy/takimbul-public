<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$migrationPath=$root.'/database/migrations/2026-09-05-v106-club-squad.sql';
expect_true(is_file($migrationPath),'V10.6 migration dosyası bulunmalı.');
$migration=is_file($migrationPath)?(string)file_get_contents($migrationPath):'';
$schema=(string)file_get_contents($root.'/database/schema.sql');
$repair=(string)file_get_contents($root.'/app/Core/SchemaRepair.php');
$launch=(string)file_get_contents($root.'/scripts/launch-check.php');
$smoke=(string)file_get_contents($root.'/scripts/production-smoke.php');
foreach(['short_name','primary_color','secondary_color','home_venue','instagram_url'] as $column){
    expect_true(str_contains($migration,$column),'Migration teams.'.$column.' alanını içermeli.');
    expect_true(str_contains($schema,$column),'Ana schema teams.'.$column.' alanını içermeli.');
}
foreach(['team_position','squad_status'] as $column){
    expect_true(str_contains($migration,$column),'Migration team_members.'.$column.' alanını içermeli.');
    expect_true(str_contains($schema,$column),'Ana schema team_members.'.$column.' alanını içermeli.');
}
expect_true(str_contains($migration,'CREATE TABLE IF NOT EXISTS team_formations'),'Migration team_formations tablosunu oluşturmalı.');
expect_true(str_contains($schema,'CREATE TABLE IF NOT EXISTS team_formations'),'Ana schema team_formations tablosunu içermeli.');
expect_true(!preg_match('/\b(DROP|TRUNCATE)\b/i',$migration),'V10.6 migration destructive SQL içermemeli.');
expect_true(str_contains($schema,'PRIMARY KEY (team_id, user_id)'),'Takım üyeliği çoklu takım desteği için composite PK olarak kalmalı.');
expect_true(!preg_match('/UNIQUE[^\n]*\(\s*user_id\s*\)/i',$migration),'V10.6 user_id tekil üyelik kısıtı eklememeli.');
expect_true(str_contains($repair,"'team_formations'"),'SchemaRepair team_formations tablosunu kritik şemada izlemeli.');
expect_true(str_contains($repair,"'team_members.team_position'"),'SchemaRepair team_position kolonunu izlemeli.');
expect_true(str_contains($launch,'V10.6 club squad migration'),'Launch check V10.6 migration dosyasını doğrulamalı.');
expect_true(str_contains($smoke,"'team_formations'"),'Production smoke team_formations okuması yapmalı.');
expect_true(str_contains($smoke,'INSERT INTO teams'),'Production smoke transactional takım yazımı yapmalı.');
expect_true(str_contains($smoke,'INSERT INTO team_members'),'Production smoke transactional takım üyeliği yazımı yapmalı.');
expect_true(str_contains($smoke,'INSERT INTO team_formations'),'Production smoke transactional diziliş yazımı yapmalı.');
expect_true(str_contains($launch,"'V10.6 club squad migration'"),'V10.6 migration launch-check kritik listesinde olmalı.');
echo "PASS v106_schema_contract_test\n";
