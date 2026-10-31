<?php

declare(strict_types=1);
$root=dirname(__DIR__);$header=(string)@file_get_contents($root.'/app/Views/layouts/header.php');$search=(string)@file_get_contents($root.'/app/Models/GlobalSearch.php');$searchView=(string)@file_get_contents($root.'/app/Views/search/index.php');$seo=(string)@file_get_contents($root.'/app/Core/Seo.php');$sitemap=(string)@file_get_contents($root.'/app/Controllers/SeoController.php');$fail=[];
if(!str_contains($header,'Profesyoneller'))$fail[]='professional nav group';
foreach(['/hakemler','/teknik-direktorler'] as $url)if(!str_contains($header,$url))$fail[]='nav '.$url;
if(!str_contains($search,"'referees'")||!str_contains($search,"'coaches'"))$fail[]='global search identity buckets';
if(!str_contains($search,'recreational_player'))$fail[]='player search identity gate';
if(!str_contains($searchView,'Hakemler')||!str_contains($searchView,'Teknik Direktörler'))$fail[]='search view identity sections';
foreach(['/hakemler','/teknik-direktorler','refereeProfile','coachProfile','Football Referee','Football Coach'] as $needle)if(!str_contains($seo,$needle))$fail[]='seo '.$needle;
if(!str_contains($sitemap,'referee_profiles')||!str_contains($sitemap,'coach_profiles'))$fail[]='sitemap professional profiles';
if(!str_contains($sitemap,"'/hakemler'")||!str_contains($sitemap,"'/teknik-direktorler'"))$fail[]='sitemap discovery pages';
if($fail!==[]){fwrite(STDERR,'FAIL: '.implode(' | ',$fail)."\n");exit(1);}echo "v108 identity seo/search contract PASS\n";
