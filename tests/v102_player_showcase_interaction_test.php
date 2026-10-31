<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$js=file_get_contents($root.'/public/assets/js/app.js')?:'';
$css=file_get_contents($root.'/public/assets/css/app.css')?:'';
$adminList=file_get_contents($root.'/app/Views/admin/amateur-ads.php')?:'';
$adminShow=file_get_contents($root.'/app/Views/admin/amateur-ad-show.php')?:'';

expect_true(str_contains($js,"input[name=\"profile_intent\"]"),'Player editor JS must react to profile intent changes.');
expect_true(str_contains($js,'[data-transfer-fields]'),'Player editor JS must control transfer-only fields.');
expect_true(str_contains($css,'.amateur-intent-options'),'Profile intent selector needs dedicated responsive styling.');
expect_true(str_contains($css,'data-transfer-fields'),'Transfer-only section needs a showcase-mode visual state.');
expect_true(str_contains($adminList,'profile_intent'),'Admin player list must expose profile intent.');
expect_true(str_contains($adminShow,'profile_intent'),'Admin player detail must expose profile intent.');

echo "PASS v102_player_showcase_interaction_test\n";
