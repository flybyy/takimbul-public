<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\Team;

expect_same([],Team::validateClubSettings([
    'short_name'=>'VRB','primary_color'=>'#128A45','secondary_color'=>'#111111','home_venue'=>'Alanya Stadı','instagram_url'=>'https://www.instagram.com/veribayt'
]),'Geçerli kulüp ayarları kabul edilmeli.');
expect_true(isset(Team::validateClubSettings(['primary_color'=>'green'])['primary_color']),'Hex olmayan ana renk reddedilmeli.');
expect_true(isset(Team::validateClubSettings(['secondary_color'=>'#12345'])['secondary_color']),'Eksik hex ikinci renk reddedilmeli.');
expect_true(isset(Team::validateClubSettings(['short_name'=>str_repeat('A',25)])['short_name']),'Kısa ad 24 karakteri aşmamalı.');
expect_true(isset(Team::validateClubSettings(['home_venue'=>str_repeat('A',161)])['home_venue']),'Saha 160 karakteri aşmamalı.');
expect_true(isset(Team::validateClubSettings(['instagram_url'=>'javascript:alert(1)'])['instagram_url']),'Güvensiz Instagram URL reddedilmeli.');
$teamSrc=(string)file_get_contents(dirname(__DIR__).'/app/Models/Team.php');
expect_true(str_contains($teamSrc,'updateClubSettings'),'Kulüp ayar güncelleme metodu bulunmalı.');
expect_true(str_contains($teamSrc,'self::isCaptain'),'Kulüp ayar update server-side kaptan yetkisi kontrol etmeli.');
$view=(string)file_get_contents(dirname(__DIR__).'/app/Views/teams/manage.php');
foreach(['Kulüp Bilgileri','Kadro','Diziliş','team_position','squad_status','match_format'] as $needle){expect_true(str_contains($view,$needle),'Yönetim ekranı '.$needle.' içermeli.');}
$routes=(string)file_get_contents(dirname(__DIR__).'/public/index.php');
expect_true(str_contains($routes,"/takim/{id}/ayarlar"),'Kulüp ayarları POST route bulunmalı.');
echo "PASS v106_team_management_test\n";
