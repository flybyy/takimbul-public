<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$schema=file_get_contents(__DIR__.'/../database/schema.sql') ?: '';
$migrationPath=__DIR__.'/../database/migrations/2026-08-31-transfer-team-value-scout.sql';
$migration=is_file($migrationPath)?(file_get_contents($migrationPath) ?: ''):'';
foreach(['CREATE TABLE IF NOT EXISTS transfer_offers','proposed_jersey_number','jersey_number','uq_team_jersey'] as $needle){
    expect_true(str_contains($schema,$needle),"Fresh schema must contain {$needle}.");
    expect_true(str_contains($migration,$needle),"Migration must contain {$needle}.");
}
expect_true(str_contains($schema,'CREATE TABLE IF NOT EXISTS scout_watchlists'), 'Fresh schema must keep scout watchlists.');
expect_true(!str_contains($migration,'DROP TABLE scout_watchlists'), 'Migration must preserve existing scout watchlist data.');
fwrite(STDOUT,"PASS v6_sql_contract_test\n");
