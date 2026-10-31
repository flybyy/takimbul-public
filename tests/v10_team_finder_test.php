<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\TeamFinder;
$root=dirname(__DIR__);
expect_true(class_exists(TeamFinder::class),'TeamFinder model missing.');
expect_true(method_exists(TeamFinder::class,'compatibilityScore'),'TeamFinder::compatibilityScore missing.');
$score=TeamFinder::compatibilityScore(
 ['province'=>'Antalya','district'=>'Alanya','primary_position'=>'Kaleci','last_seen_at'=>date('Y-m-d H:i:s')],
 ['province'=>'Antalya','district'=>'Alanya','position'=>'Kaleci','level'=>'Orta']
);
expect_true($score>=75,'Perfect local/position fit should score high.');
$index=file_get_contents($root.'/public/index.php')?:'';
expect_true(str_contains($index,"'/takim-bul'"),'Team finder route missing.');
expect_true(str_contains($index,"'/takim-ariyorum'"),'Team search profile route missing.');
expect_true(str_contains($index,"'/takim-davet/{id}/yanit'"),'Team invite response route missing.');
$schema=file_get_contents($root.'/database/schema.sql')?:'';
expect_true(str_contains($schema,'CREATE TABLE IF NOT EXISTS team_search_profiles'),'team_search_profiles table missing.');
expect_true(str_contains($schema,'CREATE TABLE IF NOT EXISTS team_invites'),'team_invites table missing.');
echo "PASS v10_team_finder_test\n";
