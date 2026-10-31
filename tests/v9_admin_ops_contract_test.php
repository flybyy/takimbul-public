<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$u=file_get_contents($root.'/app/Models/User.php')?:'';$b=file_get_contents($root.'/app/Core/Bootstrap.php')?:'';$a=file_get_contents($root.'/app/Controllers/AdminController.php')?:'';$v=is_file($root.'/app/Views/admin/settings.php')?(file_get_contents($root.'/app/Views/admin/settings.php')?:''):'';
foreach(['touchActivity','onlineCount'] as $n)$check(str_contains($u,$n),'User missing '.$n);
$check(str_contains($b,'touchActivity'),'Bootstrap must touch authenticated activity');
foreach(['operations','settings','systemHealth'] as $n)$check(str_contains($a,$n),'AdminController missing '.$n);
$check(str_contains($v,'Site Ayarları'),'admin settings view missing');
if($fail){fwrite(STDERR,"FAIL v9_admin_ops_contract_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v9_admin_ops_contract_test\n";
