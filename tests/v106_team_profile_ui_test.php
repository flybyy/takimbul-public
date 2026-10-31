<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$view=(string)file_get_contents($root.'/app/Views/teams/show.php');
$controller=(string)file_get_contents($root.'/app/Controllers/TeamController.php');
$css=(string)file_get_contents($root.'/public/assets/css/app.css');
foreach(['Genel Bakış','Kadro','Diziliş','Maçlar','İstatistik','Transferler'] as $tab){
    expect_true(str_contains($view,$tab),'Takım profilinde '.$tab.' bölümü bulunmalı.');
}
expect_true(str_contains($view,'squad-card.php'),'Takım profili reusable squad-card kullanmalı.');
expect_true(str_contains($view,'tb-roster-table'),'Yoğun veri için erişilebilir kadro tablosu bulunmalı.');
expect_true(str_contains($view,'tb-formation-pitch'),'Public diziliş saha görünümü bulunmalı.');
expect_true(str_contains($view,'SON 5'),'Son beş form görünümü bulunmalı.');
expect_true(!str_contains($view,"['phone']") && !str_contains($view,"['email']"),'Public takım görünümü telefon/e-posta render etmemeli.');
expect_true(str_contains($controller,'TeamStats::forTeam'),'Controller gerçek takım istatistiklerini yüklemeli.');
expect_true(str_contains($controller,'TeamFormation::find'),'Controller public dizilişi yüklemeli.');
expect_true(str_contains($css,'TAKIMBUL V10.6 CLUB SQUAD'),'V10.6 özgün futbol veri stili CSS katmanı bulunmalı.');
expect_true(str_contains($css,'.tb-squad-card'),'Squad card responsive stil tanımı bulunmalı.');
expect_true(str_contains($css,'.tb-formation-pitch'),'Saha görünümü stil tanımı bulunmalı.');
echo "PASS v106_team_profile_ui_test\n";
