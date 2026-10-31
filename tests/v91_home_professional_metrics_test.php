<?php
declare(strict_types=1);
$home=file_get_contents(dirname(__DIR__).'/app/Views/home.php') ?: '';
$css=file_get_contents(dirname(__DIR__).'/public/assets/css/app.css') ?: '';
$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$check(str_contains($home,'home-network-pulse'),'Professional network pulse block missing.');
$check(str_contains($home,'formatHomeMetric'),'Home metric formatter missing.');
$check(str_contains($home,"counts['players']"),'Real player count must remain data-driven.');
$check(str_contains($css,'.home-network-pulse'),'Professional network pulse styling missing.');
$check(!str_contains($home,'Rakamlar platformdaki gerçek kayıtları gösterir.'),'Old explanatory metric copy should be removed.');
if($fail){fwrite(STDERR,"FAIL v91_home_professional_metrics_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v91_home_professional_metrics_test\n";
