<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$user=file_get_contents($root.'/app/Models/User.php')?:'';
$start=strpos($user,'public static function adminStats()');
$end=strpos($user,'public static function recentForAdmin', $start===false?0:$start);
$body=($start!==false&&$end!==false)?substr($user,$start,$end-$start):'';
expect_true($body!=='','adminStats source missing.');
expect_true(str_contains($body,'optionalAdminCount'),'adminStats must use optional counts for v10-only tables.');
expect_true(!preg_match('/SELECT COUNT\(\*\) FROM team_search_profiles[^;]*AS active_team_seekers/s',$body),'Main adminStats query must not hard-depend on team_search_profiles.');
expect_true(!preg_match('/SELECT COUNT\(\*\) FROM team_invites[^;]*AS pending_team_invites/s',$body),'Main adminStats query must not hard-depend on team_invites.');
echo "PASS v10_admin_stats_resilience_test\n";
