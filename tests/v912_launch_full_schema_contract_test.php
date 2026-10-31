<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';

$root=dirname(__DIR__);
$schema=file_get_contents($root.'/database/schema.sql')?:'';
$check=file_get_contents($root.'/scripts/launch-check.php')?:'';
preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`?([a-zA-Z0-9_]+)`?/i',$schema,$m);
$tables=array_values(array_unique($m[1]??[]));
expect_true(count($tables)>=30,'Güncel şema tüm modül tablolarını içermeli.');
foreach($tables as $table){
    expect_true(str_contains($check,"'{$table}'"),"Launch-check eksik tablo kontrolü: {$table}");
}
expect_true(str_contains($check,"'avatar klasörü yazılabilir'"),'Avatar klasörü launch-check içinde kontrol edilmeli.');
expect_true((bool)preg_match('/\$criticalNames=\[[^\]]*\'avatar klasörü yazılabilir\'/s',$check),'Avatar yazma kontrolü kritik kontroller içinde olmalı.');

echo "PASS v912_launch_full_schema_contract_test\n";
