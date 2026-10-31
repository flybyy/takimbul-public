<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$schema=file_get_contents(__DIR__.'/../database/schema.sql') ?: '';
$migrationPath=__DIR__.'/../database/migrations/2026-08-31-ad-manager.sql';
$migration=is_file($migrationPath)?(file_get_contents($migrationPath) ?: ''):'';
foreach(['type ENUM(\'manual\',\'external_code\')','image_url','external_code','device_target','impressions','clicks','updated_at'] as $needle){
    expect_true(str_contains($schema,$needle),"Fresh schema must contain {$needle}.");
    expect_true(str_contains($migration,$needle),"Migration must contain {$needle}.");
}
foreach(['home_feed','home_sidebar','players_feed','opponents_feed','match_inline','team_inline','career_inline','mobile_feed'] as $slot){
    expect_true(str_contains($schema,$slot),"Schema placement enum must include {$slot}.");
}
echo "PASS v8_sql_contract_test\n";
