<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$schema=file_get_contents($root.'/database/schema.sql')?:'';
$migration=is_file($root.'/database/migrations/2026-09-01-v9-matchday.sql')?(file_get_contents($root.'/database/migrations/2026-09-01-v9-matchday.sql')?:''):'';
foreach(['player_availabilities','availability_invites','match_attendance','player_videos','site_settings'] as $table){$check(str_contains($schema,'CREATE TABLE IF NOT EXISTS '.$table),'schema missing '.$table);$check(str_contains($migration,$table),'migration missing '.$table);}
$check(str_contains($schema,'last_seen_at'),'schema missing users.last_seen_at');
$check(str_contains($migration,'last_seen_at'),'migration missing users.last_seen_at');
if($fail){fwrite(STDERR,"FAIL v9_sql_contract_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v9_sql_contract_test\n";
