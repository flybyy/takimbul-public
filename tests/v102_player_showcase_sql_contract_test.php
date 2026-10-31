<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$schema=file_get_contents($root.'/database/schema.sql')?:'';
$migrationPath=$root.'/database/migrations/2026-09-04-v102-player-showcase.sql';
expect_true(is_file($migrationPath),'v10.2 player showcase migration missing.');
$migration=is_file($migrationPath)?(file_get_contents($migrationPath)?:''):'';
expect_true(str_contains($schema,"profile_intent ENUM('team_search','showcase','both') NOT NULL DEFAULT 'team_search'"),'Fresh schema is missing profile_intent.');
expect_true(str_contains($migration,'profile_intent'),'Migration is missing profile_intent.');
expect_true(str_contains($migration,"DEFAULT 'team_search'")||str_contains($migration,"DEFAULT \'team_search\'"),'Migration must preserve existing ads as team-search profiles.');
expect_true(!preg_match('/\bDROP\s+TABLE\b/i',$migration),'Migration must not drop tables.');
expect_true(!preg_match('/\bTRUNCATE\b/i',$migration),'Migration must not truncate data.');

$model=file_get_contents($root.'/app/Models/AmateurPlayerAd.php')?:'';
expect_true(str_contains($model,'profile_intent=:profile_intent'),'Update SQL must persist profile_intent.');
expect_true(str_contains($model,'profile_intent,secondary_position'),'Insert SQL must persist profile_intent.');
expect_true(str_contains($model,'SET status="active",profile_intent="showcase",expires_at=NULL,found_team_at=NOW()'),'Takımımı Buldum must keep the player published as showcase-only.');

$repair=file_get_contents($root.'/app/Core/SchemaRepair.php')?:'';
expect_true(str_contains($repair,'amateur_player_ads.profile_intent'),'Production repair must treat profile_intent as runtime-critical.');

$smoke=file_get_contents($root.'/scripts/production-smoke.php')?:'';
expect_true(str_contains($smoke,'profile_intent'),'Production smoke must exercise the new profile_intent column.');

$launch=file_get_contents($root.'/scripts/launch-check.php')?:'';
expect_true(str_contains($launch,"['amateur_player_ads','profile_intent']"),'Launch check must verify profile_intent column.');
expect_true(str_contains($launch,'2026-09-04-v102-player-showcase.sql'),'Launch check must verify the v10.2 migration file.');

echo "PASS v102_player_showcase_sql_contract_test\n";
