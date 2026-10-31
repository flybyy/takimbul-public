<?php

declare(strict_types=1);
$root=dirname(__DIR__);
$auth=(string)@file_get_contents($root.'/app/Controllers/AuthController.php');
$user=(string)@file_get_contents($root.'/app/Models/User.php');
$view=(string)@file_get_contents($root.'/app/Views/auth/register.php');
$account=(string)@file_get_contents($root.'/app/Controllers/AccountController.php');
$accountView=(string)@file_get_contents($root.'/app/Views/account/index.php');
$routes=(string)@file_get_contents($root.'/public/index.php');
$fail=[];
foreach(['recreational_player','amateur_player','referee','coach','club_official'] as $type){if(!str_contains($view,$type))$fail[]='register missing '.$type;}
if(!str_contains($view,'Birden fazla') && !str_contains($view,'Birden Fazla'))$fail[]='multi identity copy';
if(!str_contains($auth,'FootballIdentity::syncForUser'))$fail[]='registration identity persistence';
if(!str_contains($auth,'nextOnboardingPath'))$fail[]='identity onboarding redirect';
if(!str_contains($user,'FootballIdentity::normalizeTypes'))$fail[]='server selection validation';
if(!str_contains($account,'FootballIdentity::forUser'))$fail[]='account identity load';
if(!str_contains($accountView,'Yeni futbol kimliği ekle'))$fail[]='account add identity UI';
if(!str_contains($routes,"/hesabim/futbol-kimligi"))$fail[]='identity add route';
if(!str_contains($auth,"? 'public' : 'private'"))$fail[]='non-recreational must not become public halisaha profile';
if($fail!==[]){fwrite(STDERR,'FAIL: '.implode(' | ',$fail)."\n");exit(1);}echo "v108 identity registration contract PASS\n";
