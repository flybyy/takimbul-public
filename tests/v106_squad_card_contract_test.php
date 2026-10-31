<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$component=dirname(__DIR__).'/app/Views/components/squad-card.php';
expect_true(is_file($component),'Squad card component oluşturulmalı.');
if(is_file($component)){
    $member=['id'=>5,'display_name'=>'Murat Dinçer','jersey_number'=>7,'team_position'=>'Kanat','primary_position'=>'Orta Saha','role'=>'player','squad_status'=>'starter','form_score'=>0,'form_label'=>'Veri Bekleniyor','appearances'=>0,'goals'=>0,'assists'=>0,'avatar_path'=>null];
    $team=['primary_color'=>'#1f8f4e'];
    ob_start(); include $component; $html=(string)ob_get_clean();
    expect_true(str_contains($html,'Yeni Oyuncu')||str_contains($html,'Veri Bekleniyor'),'Verisiz oyuncu kartında sahte skor yerine veri bekleniyor mesajı olmalı.');
    expect_true(!str_contains($html,'0 OVR'),'Kart sahte FIFA OVR göstermemeli.');
    expect_true(str_contains($html,'Murat Dinçer'),'Kart oyuncu adını göstermeli.');
    expect_true(str_contains($html,'#7'),'Kart forma numarasını göstermeli.');
    $member['form_score']=78;$member['form_label']='İyi Form';$member['appearances']=12;$member['goals']=7;$member['assists']=5;
    ob_start(); include $component; $html2=(string)ob_get_clean();
    expect_true(str_contains($html2,'78'),'Gerçek form skoru varsa kartta gösterilmeli.');
    expect_true(str_contains($html2,'TakımBul Form'),'Skorun TakımBul metriği olduğu açık olmalı.');
}
$stats=dirname(__DIR__).'/app/Models/TeamStats.php';
expect_true(is_file($stats),'TeamStats modeli oluşturulmalı.');
if(is_file($stats)){
    $src=(string)file_get_contents($stats);
    expect_true(str_contains($src,'PlayerValue::calculate'),'Takım oyuncu form skoru mevcut deterministik PlayerValue hesaplamasını kullanmalı.');
    expect_true(str_contains($src,'player_match_stats'),'Takım istatistikleri doğrulanmış maç istatistiklerinden gelmeli.');
}
echo "PASS v106_squad_card_contract_test\n";
