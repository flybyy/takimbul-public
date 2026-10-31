<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$migration=file_get_contents($root.'/database/migrations/2026-09-01-v10-ultimate-platform.sql')?:'';
expect_true($migration!=='','v10 migration missing.');
expect_true(!str_contains(strtoupper($migration),'ADD COLUMN IF NOT EXISTS'),'Migration must not depend on ADD COLUMN IF NOT EXISTS for shared-host compatibility.');
expect_true(str_contains($migration,'information_schema.COLUMNS'),'Migration must detect privacy_accepted_at via information_schema before ALTER.');
expect_true(str_contains($migration,'PREPARE'),'Migration must use idempotent prepared ALTER pattern.');
foreach(['admin_user_notes','team_search_profiles','team_invites'] as $table){
    expect_true(str_contains($migration,'CREATE TABLE IF NOT EXISTS '.$table),"Migration missing {$table}.");
}
foreach(['home_top','welcome_takeover'] as $placement){
    expect_true(str_contains($migration,"'{$placement}'"),"Migration missing ad placement {$placement}.");
}
echo "PASS v10_migration_compatibility_test\n";
