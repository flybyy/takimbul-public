<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$header=file_get_contents($root.'/app/Views/layouts/header.php')?:'';
$available=file_get_contents($root.'/app/Views/availability/index.php')?:'';
$adminSettings=file_get_contents($root.'/app/Views/admin/settings.php')?:'';
$check(str_contains($header,'maintenance_message') && str_contains($header,'site-operation-message'),'maintenance/operation message must render from admin setting');
$check(str_contains($available,'avatar_path'),'available player cards must use real profile avatars when present');
$check(str_contains($adminSettings,"errors['support_email']") && str_contains($adminSettings,"errors['maintenance_message']"),'admin settings must show validation errors next to support email and operation message');
if($fail){fwrite(STDERR,"FAIL v9_polish_contract_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v9_polish_contract_test\n";
