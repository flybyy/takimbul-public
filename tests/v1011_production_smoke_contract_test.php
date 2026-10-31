<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';

$root=dirname(__DIR__);
$file=$root.'/scripts/production-smoke.php';
expect_true(is_file($file),'Production runtime smoke CLI must exist.');
$src=(string)@file_get_contents($file);
expect_true(str_contains($src,'beginTransaction()'),'Write smoke must start a transaction.');
expect_true(str_contains($src,'rollBack()'),'Write smoke must roll temporary data back.');
expect_true(str_contains($src,'privacy_accepted_at'),'Write smoke must exercise current registration columns.');
expect_true(str_contains($src,'player_profiles'),'Write smoke must exercise profile creation.');
foreach(['player_availabilities','availability_invites','amateur_player_ads','ads','site_settings','team_search_profiles','team_invites'] as $table){
    expect_true(str_contains($src,$table),"Runtime smoke missing high-risk table {$table}.");
}
expect_true(!preg_match('/\b(DROP|TRUNCATE)\b/i',$src),'Runtime smoke must not contain destructive SQL.');

echo "PASS v1011_production_smoke_contract_test\n";
