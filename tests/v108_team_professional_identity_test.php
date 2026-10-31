<?php

declare(strict_types=1);
$root=dirname(__DIR__);$routes=(string)@file_get_contents($root.'/public/index.php');$teamController=(string)@file_get_contents($root.'/app/Controllers/TeamController.php');$matchController=(string)@file_get_contents($root.'/app/Controllers/MatchController.php');$fail=[];
foreach(['TeamStaffMembership.php','RefereeMatchAssignment.php'] as $f)if(!is_file($root.'/app/Models/'.$f))$fail[]='missing '.$f;
foreach(['/takim/{id}/teknik-ekip','/takim/{id}/teknik-ekip/{membershipId}/ayril','/mac/{id}/hakem-davet','/hakem-atama/{id}/yanit'] as $route)if(!str_contains($routes,$route))$fail[]='route '.$route;
if(!str_contains($teamController,'TeamStaffMembership::add'))$fail[]='team staff add';
if(!str_contains($matchController,'RefereeMatchAssignment::invite'))$fail[]='referee invite';
$teamShow=(string)@file_get_contents($root.'/app/Views/teams/show.php');if(!str_contains($teamShow,'Teknik Ekip'))$fail[]='public technical staff';
$matchShow=(string)@file_get_contents($root.'/app/Views/matches/show.php');if(!str_contains($matchShow,'Hakem'))$fail[]='match referee section';
$staff=(string)@file_get_contents($root.'/app/Models/TeamStaffMembership.php');if(str_contains($staff,'DELETE FROM team_members')||str_contains($staff,'UPDATE team_members'))$fail[]='staff mutates player membership';
if($fail!==[]){fwrite(STDERR,'FAIL: '.implode(' | ',$fail)."\n");exit(1);}echo "v108 team professional identity PASS\n";
