<?php

declare(strict_types=1);
$root=dirname(__DIR__);
spl_autoload_register(function(string $class) use($root): void {$prefix='App\\';if(!str_starts_with($class,$prefix))return;$path=$root.'/app/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';if(is_file($path))require $path;});
$model=$root.'/app/Models/RefereeProfile.php';$controller=$root.'/app/Controllers/RefereeController.php';$routes=(string)@file_get_contents($root.'/public/index.php');
$fail=[];
if(!is_file($model))$fail[]='model missing';
if(!is_file($controller))$fail[]='controller missing';
foreach(['/hakemler','/hakem/{slug}','/hakem-profilim'] as $route)if(!str_contains($routes,$route))$fail[]='route '.$route;
if(is_file($model)){
    $e=App\Models\RefereeProfile::validateInput(['province'=>'Antalya','district'=>'Çankaya','started_year'=>'2020','availability_status'=>'available','fee_visibility'=>'1','fee_min'=>'500','fee_max'=>'300']);
    if(!isset($e['district']))$fail[]='location pair validation';
    if(!isset($e['fee_max']))$fail[]='fee range validation';
    $ok=App\Models\RefereeProfile::validateInput(['province'=>'Antalya','district'=>'Alanya','classification'=>'İl Hakemi','started_year'=>'2020','availability_status'=>'available','fee_visibility'=>'0','bio'=>'Deneyimli hakem']);
    if($ok!==[])$fail[]='valid referee input '.json_encode($ok,JSON_UNESCAPED_UNICODE);
}
$show=(string)@file_get_contents($root.'/app/Views/referees/show.php');
if($show!=='' && (str_contains($show,'phone')||str_contains($show,'email'))) $fail[]='public referee contact leak';
if($fail!==[]){fwrite(STDERR,'FAIL: '.implode(' | ',$fail)."\n");exit(1);}echo "v108 referee profile PASS\n";
