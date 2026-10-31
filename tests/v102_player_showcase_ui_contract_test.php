<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$form=file_get_contents($root.'/app/Views/amateur-market/form.php')?:'';
$index=file_get_contents($root.'/app/Views/amateur-market/index.php')?:'';
$show=file_get_contents($root.'/app/Views/amateur-market/show.php')?:'';
$controller=file_get_contents($root.'/app/Controllers/AmateurMarketController.php')?:'';
$home=file_get_contents($root.'/app/Views/home.php')?:'';

expect_true(str_contains($form,'name="profile_intent"'),'Player editor is missing profile intent selection.');
foreach(['Takım arıyorum','Kendimi tanıtıyorum','İkisi de'] as $copy)expect_true(str_contains($form,$copy),'Player editor missing intent copy: '.$copy);
expect_true(str_contains($index,'name="profile_intent"'),'Public player list is missing intent filter.');
expect_true(str_contains($index,'OYUNCU VİTRİNİ'),'Player cards are missing showcase badge.');
expect_true(str_contains($show,'OYUNCU VİTRİNİ'),'Player detail is missing showcase badge.');
expect_true(str_contains($show,'AmateurPlayerAd::isTransferOpen($ad)'),'Player detail must gate team invite UI by transfer intent.');
expect_true(str_contains($controller,'AmateurPlayerAd::isTransferOpen($ad)'),'Invite endpoint must enforce transfer intent server-side.');
expect_true(str_contains($controller,"'profile_intent'=>'both'"),'New player profile form should default to combined showcase + team search.');

expect_true(str_contains($controller,'Transfer arayışın kapatıldı'),'Found-team action should explain that the profile remains as showcase.');
expect_true(str_contains($form,'Profilin yayında'),'Player editor should use profile language for active state.');
expect_true(str_contains($show,'Profili bildir'),'Player detail should use profile language for reporting.');
expect_true(str_contains($home,'Kendini göster, takımını bul'),'Home page should advertise showcase and transfer use together.');
expect_true(str_contains($home,'Oyuncu profilini oluştur'),'Home page CTA should create a player profile, not only a transfer ad.');

echo "PASS v102_player_showcase_ui_contract_test\n";
