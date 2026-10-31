<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$routes=file_get_contents(__DIR__.'/../public/index.php') ?: '';
$career=file_get_contents(__DIR__.'/../app/Views/career/show.php') ?: '';
$teamManage=is_file(__DIR__.'/../app/Views/teams/manage.php') ? (file_get_contents(__DIR__.'/../app/Views/teams/manage.php') ?: '') : '';
$transfers=is_file(__DIR__.'/../app/Views/transfers/index.php') ? (file_get_contents(__DIR__.'/../app/Views/transfers/index.php') ?: '') : '';
$watchlist=is_file(__DIR__.'/../app/Views/scout/watchlist.php') ? (file_get_contents(__DIR__.'/../app/Views/scout/watchlist.php') ?: '') : '';
$css=file_get_contents(__DIR__.'/../public/assets/css/app.css') ?: '';
foreach(['/transferler','/transfer/teklif/{id}','/takim/{id}/yonet','/scout/takip-listem'] as $needle){
    expect_true(str_contains($routes,$needle),"Route {$needle} must exist.");
}
expect_true(str_contains($career,'TakımBul Değeri'), 'Career must show TakımBul Value.');
expect_true(str_contains($career,'Takımıma Davet Et'), 'Career must expose transfer CTA for managers.');
expect_true(str_contains($career,'Takibe Al'), 'Career must expose scout watchlist CTA.');
expect_true(str_contains($teamManage,'Forma No') && str_contains($teamManage,'Yardımcı kaptan'), 'Team management must expose jersey and vice captain controls.');
expect_true(str_contains($transfers,'Transfer Teklifleri'), 'Transfer center must exist.');
expect_true(str_contains($watchlist,'Takip Listem'), 'Scout watchlist view must exist.');
foreach(['.career-actions','.transfer-card','.team-manage-grid','.value-score','.watchlist-grid'] as $needle){
    expect_true(str_contains($css,$needle),"CSS must contain {$needle}.");
}
expect_true(str_contains($css,'@media(max-width:700px)'), 'v6 must preserve mobile-first breakpoint.');
fwrite(STDOUT,"PASS v6_ui_test\n");
