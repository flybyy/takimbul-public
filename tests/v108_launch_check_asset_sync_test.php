<?php

declare(strict_types=1);
$root=dirname(__DIR__);
$src=(string)file_get_contents($root.'/scripts/launch-check.php');
$errors=[];
if(str_contains($src,'$add(\'V10.7.1 asset senkronu\',\'V10.8 football identities asset senkronu\'')){
    $errors[]='V10.7.1 asset check must not pass a label string as the boolean condition';
}
if(!str_contains($src,'$add(\'V10.7.1 asset senkronu\',str_contains($css,\'TAKIMBUL V10.7.1 HOTFIX\')')){
    $errors[]='V10.7.1 asset check must evaluate the hotfix marker as its boolean condition';
}

foreach(['app/Views/referees/index.php','app/Views/referees/form.php','app/Views/referees/show.php','app/Views/coaches/index.php','app/Views/coaches/form.php','app/Views/coaches/show.php','app/Views/admin/referee-profile.php','app/Views/admin/coach-profile.php'] as $view){
    if(!str_contains($src,$view)){$errors[]='launch-check critical views missing '.$view;}
}

if($errors){fwrite(STDERR,"FAIL\n - ".implode("\n - ",$errors)."\n");exit(1);} 
echo "PASS v108_launch_check_asset_sync_test\n";
