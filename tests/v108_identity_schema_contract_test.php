<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$migration=$root.'/database/migrations/2026-09-08-v108-football-identities.sql';
$schema=(string)@file_get_contents($root.'/database/schema.sql');
$repair=(string)@file_get_contents($root.'/app/Core/SchemaRepair.php');
$launch=(string)@file_get_contents($root.'/scripts/launch-check.php');
$smoke=(string)@file_get_contents($root.'/scripts/production-smoke.php');

$fail=[];
if(!is_file($migration))$fail[]='migration missing';
$sql=(string)@file_get_contents($migration);
foreach(['user_football_identities','referee_profiles','coach_profiles','coach_career_entries','team_staff_memberships','referee_match_assignments'] as $table){
    if(!str_contains($sql,'CREATE TABLE IF NOT EXISTS '.$table) && !str_contains($sql,'CREATE TABLE IF NOT EXISTS `'.$table.'`'))$fail[]='migration table '.$table;
    if(!str_contains($schema,'CREATE TABLE IF NOT EXISTS '.$table) && !str_contains($schema,'CREATE TABLE IF NOT EXISTS `'.$table.'`'))$fail[]='schema table '.$table;
}
if(!preg_match('/UNIQUE\s+KEY\s+[^\n]*\(user_id,\s*identity_type\)/i',$sql))$fail[]='identity unique user/type';
if(preg_match('/\b(DROP|TRUNCATE)\b/i',$sql))$fail[]='destructive sql';
if(!str_contains($sql,'amateur_player_ads') || !str_contains($sql,"'amateur_player'"))$fail[]='amateur backfill';
if(!str_contains($repair,'user_football_identities') || !str_contains($repair,'referee_profiles') || !str_contains($repair,'coach_profiles'))$fail[]='repair critical tables/backfill';
if(!str_contains($launch,'V10.8 football identities migration'))$fail[]='launch migration gate';
if(!str_contains($smoke,'user_football_identities') || !str_contains($smoke,'referee_profiles') || !str_contains($smoke,'coach_profiles'))$fail[]='production smoke identities';

if($fail!==[]){fwrite(STDERR,"FAIL: ".implode(' | ',$fail)."\n");exit(1);}echo "v108 identity schema contract PASS\n";
