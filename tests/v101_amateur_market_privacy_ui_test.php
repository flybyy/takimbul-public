<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$show=is_file($root.'/app/Views/amateur-market/show.php')?(file_get_contents($root.'/app/Views/amateur-market/show.php')?:''):'';
$form=is_file($root.'/app/Views/amateur-market/form.php')?(file_get_contents($root.'/app/Views/amateur-market/form.php')?:''):'';
expect_true(str_contains($show,'Oyuncunun beyan ettiği kariyer geçmişi'),'Self-declared career label missing.');
expect_true(str_contains($show,'Toplam öz-beyan'),'Self-declared summary label missing.');
expect_true(!str_contains($show,'$ad[\'phone\']'),'Public detail must not directly render phone from ad.');
expect_true(str_contains($form,'career['),'Career row inputs missing.');
expect_true(str_contains($form,'Takımımı Buldum'),'Found-team action missing from owner UI.');
expect_true(str_contains($show,'Sponsorlu') || str_contains($show,"AdRenderer::slot('amateur_detail_inline'"),'Detail ad slot missing.');
echo "PASS v101_amateur_market_privacy_ui_test\n";
