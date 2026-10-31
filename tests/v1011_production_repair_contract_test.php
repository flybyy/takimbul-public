<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';

$root=dirname(__DIR__);
$core=$root.'/app/Core/SchemaRepair.php';
$script=$root.'/scripts/production-repair.php';
expect_true(is_file($core),'SchemaRepair core must exist.');
expect_true(is_file($script),'Production repair CLI must exist.');
$src=(string)@file_get_contents($core)."\n".(string)@file_get_contents($script);
expect_true(str_contains($src,'function audit'),'Repair engine must expose an audit phase.');
expect_true(str_contains($src,'function apply'),'Repair engine must expose an explicit apply phase.');
expect_true(str_contains($src,'--apply'),'Repair CLI must require explicit --apply for mutations.');
expect_true(str_contains($src,'schema.sql'),'Repair must use current schema as the missing-table source of truth.');
expect_true(str_contains($src,'information_schema.COLUMNS'),'Repair must inspect columns before ALTER.');
expect_true(!preg_match('/\b(DROP|TRUNCATE)\s+(TABLE|DATABASE|\w+)/i',$src),'Production repair must not contain destructive DROP/TRUNCATE operations.');
foreach(['users','player_availabilities','availability_invites','site_settings','admins','amateur_player_ads','amateur_player_career_entries','ads'] as $name){
    expect_true(str_contains($src,$name),"Repair coverage missing {$name}.");
}
foreach(['privacy_accepted_at','last_seen_at','updated_by_admin_id','reviewed_by_admin_id'] as $column){
    expect_true(str_contains($src,$column),"Repair coverage missing column {$column}.");
}

echo "PASS v1011_production_repair_contract_test\n";
