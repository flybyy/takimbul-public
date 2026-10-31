<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$routes=file_get_contents($root.'/public/index.php')?:'';
$home=file_get_contents($root.'/app/Views/home.php')?:'';
$header=(file_get_contents($root.'/app/Views/layouts/header.php')?:'').(file_get_contents($root.'/app/Views/layouts/footer.php')?:'');
$match=file_get_contents($root.'/app/Views/matches/show.php')?:'';
foreach(['/musaitim','/musait-oyuncular','/rakip-bul/anlik','/mac/{id}/katilim','/kariyer/vitrin','/admin/ayarlar'] as $route)$check(str_contains($routes,$route),'route missing '.$route);
foreach(['v9-command','Müsaitim','Anlık Rakip Bul','Bu Akşam'] as $copy)$check(str_contains($home,$copy),'home missing '.$copy);
$check(!str_contains($home,'120K+'),'fake player metric must be removed');
$check(!str_contains($home,'8.4K+'),'fake team metric must be removed');
$check(!str_contains($home,'25K+'),'fake post metric must be removed');
$check(str_contains($header,'v9-mobile-sheet'),'mobile action sheet missing');
$check(str_contains($match,'matchday-shell'),'matchday shell missing');
if($fail){fwrite(STDERR,"FAIL v9_ui_contract_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v9_ui_contract_test\n";
