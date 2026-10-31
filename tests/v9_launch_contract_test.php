<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$s=file_get_contents($root.'/scripts/launch-check.php')?:'';
foreach(['v9 Müsaitim route','v9 Matchday katılım route','v9 migration'] as $label)$check(str_contains($s,$label),'launch-check missing '.$label);
$check(str_contains($s,'2026-09-01-v9-matchday.sql'),'launch-check must verify v9 migration artifact');
if($fail){fwrite(STDERR,"FAIL v9_launch_contract_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v9_launch_contract_test\n";
