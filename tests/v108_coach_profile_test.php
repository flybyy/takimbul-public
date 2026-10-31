<?php

declare(strict_types=1);
$root=dirname(__DIR__);spl_autoload_register(function(string $class) use($root): void {$prefix='App\\';if(!str_starts_with($class,$prefix))return;$path=$root.'/app/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';if(is_file($path))require $path;});
$routes=(string)@file_get_contents($root.'/public/index.php');$fail=[];
foreach(['/teknik-direktorler','/teknik-direktor/{slug}','/teknik-direktor-profilim'] as $route)if(!str_contains($routes,$route))$fail[]='route '.$route;
if(!class_exists(App\Models\CoachProfile::class))$fail[]='coach model';
if(!class_exists(App\Models\CoachCareerEntry::class))$fail[]='career model';
if(class_exists(App\Models\CoachProfile::class)){
 $bad=App\Models\CoachProfile::validateInput(['province'=>'Antalya','district'=>'Çankaya','public_title'=>'Teknik Direktör','intent'=>'showcase']);if(!isset($bad['district']))$fail[]='location validation';
 $ok=App\Models\CoachProfile::validateInput(['province'=>'Antalya','district'=>'Alanya','public_title'=>'Teknik Direktör','license_level'=>'UEFA B','intent'=>'open_to_offers','specialties'=>['Genç oyuncu gelişimi'],'formations'=>['4-3-3'],'bio'=>'Teknik direktör']);if($ok!==[])$fail[]='valid input '.json_encode($ok,JSON_UNESCAPED_UNICODE);
}
if(class_exists(App\Models\CoachCareerEntry::class)){
 $bad=App\Models\CoachCareerEntry::validateRows([['season'=>'2025/26','team_name'=>'A','role'=>'TD','matches'=>5,'wins'=>4,'draws'=>2,'losses'=>0]]);if($bad===[])$fail[]='career arithmetic';
}
$show=(string)@file_get_contents($root.'/app/Views/coaches/show.php');if($show!==''&&(str_contains($show,'phone')||str_contains($show,'email'))) $fail[]='public coach contact leak';
if($fail!==[]){fwrite(STDERR,'FAIL: '.implode(' | ',$fail)."\n");exit(1);}echo "v108 coach profile PASS\n";
