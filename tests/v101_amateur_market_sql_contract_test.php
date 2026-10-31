<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$schema=file_get_contents($root.'/database/schema.sql')?:'';
$migrationPath=$root.'/database/migrations/2026-09-02-v101-amateur-player-market.sql';
expect_true(is_file($migrationPath),'v10.1 migration missing.');
$migration=is_file($migrationPath)?(file_get_contents($migrationPath)?:''):'';
foreach(['amateur_player_ads','amateur_player_career_entries','amateur_player_ad_events','amateur_player_favorites'] as $table){
 expect_true(str_contains($schema,'CREATE TABLE IF NOT EXISTS '.$table),$table.' missing from fresh schema.');
 expect_true(str_contains($migration,'CREATE TABLE IF NOT EXISTS '.$table),$table.' missing from migration.');
}
expect_true(str_contains($schema,'found_team_at'),'found_team_at missing.');
expect_true(str_contains($schema,'featured_until'),'featured_until missing.');
expect_true(str_contains($schema,'self_declared'),'Career verification marker missing.');
expect_true(str_contains($migration,'amateur_list_top'),'New amateur ad placements missing from migration.');
expect_true(!preg_match('/\bDROP\s+TABLE\b/i',$migration),'Migration must not drop tables.');
expect_true(!preg_match('/\bTRUNCATE\b/i',$migration),'Migration must not truncate data.');
echo "PASS v101_amateur_market_sql_contract_test\n";
