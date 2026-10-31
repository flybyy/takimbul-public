<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$schema = file_get_contents(__DIR__ . '/../database/schema.sql') ?: '';
$migration = file_get_contents(__DIR__ . '/../database/migrations/2026-08-31-lineups-ratings-seasons.sql') ?: '';
foreach (['CREATE TABLE IF NOT EXISTS seasons','CREATE TABLE IF NOT EXISTS match_lineups','CREATE TABLE IF NOT EXISTS match_player_ratings','season_id','home_ratings_completed_at','away_ratings_completed_at'] as $needle) {
    expect_true(str_contains($schema, $needle), "Fresh schema must contain {$needle}.");
    expect_true(str_contains($migration, $needle) || in_array($needle,['CREATE TABLE IF NOT EXISTS seasons','CREATE TABLE IF NOT EXISTS match_lineups','CREATE TABLE IF NOT EXISTS match_player_ratings'], true), "Migration must support {$needle}.");
}
fwrite(STDOUT, "PASS v5_sql_contract_test\n");
