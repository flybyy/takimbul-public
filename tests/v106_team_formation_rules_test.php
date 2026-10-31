<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\TeamFormation;

$templates=TeamFormation::templates();
foreach(['5v5','6v6','7v7','8v8','11v11'] as $format){
    expect_true(isset($templates[$format]),$format.' template bulunmalı.');
    $tpl=$templates[$format];
    expect_same((int)substr($format,0,-2),count($tpl['slots']),$format.' slot sayısı formatla eşleşmeli.');
    foreach($tpl['slots'] as $slot){
        expect_true($slot['x']>=0 && $slot['x']<=100 && $slot['y']>=0 && $slot['y']<=100,'Slot koordinatları saha içinde kalmalı.');
    }
}
$valid=TeamFormation::validate('7v7',$templates['7v7']['code'],[
    'gk'=>1,'d1'=>2,'d2'=>3,'m1'=>4,'m2'=>5,'f1'=>6,'f2'=>7,
],[1,2,3,4,5,6,7,8]);
expect_same([],$valid['errors'],'Geçerli 7v7 dizilişi kabul edilmeli.');
expect_same(7,count($valid['slots']),'7v7 normalize edilmiş slot sayısı 7 olmalı.');
$duplicate=TeamFormation::validate('5v5',$templates['5v5']['code'],['gk'=>1,'d1'=>1],[1,2,3]);
expect_true(isset($duplicate['errors']['assignments']),'Aynı oyuncu iki slota atanamamalı.');
$outsider=TeamFormation::validate('5v5',$templates['5v5']['code'],['gk'=>99],[1,2,3]);
expect_true(isset($outsider['errors']['assignments']),'Takım dışı oyuncu atanamamalı.');
$badFormat=TeamFormation::validate('9v9','x',[],[1,2]);
expect_true(isset($badFormat['errors']['match_format']),'Bilinmeyen format reddedilmeli.');
$badCode=TeamFormation::validate('7v7','4-4-2',[],[1,2]);
expect_true(isset($badCode['errors']['formation_code']),'Format için bilinmeyen şablon reddedilmeli.');
echo "PASS v106_team_formation_rules_test\n";
