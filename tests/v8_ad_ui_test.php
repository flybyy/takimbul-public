<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$home=file_get_contents(__DIR__.'/../app/Views/home.php') ?: '';
$players=file_get_contents(__DIR__.'/../app/Views/market/players.php') ?: '';
$opponents=file_get_contents(__DIR__.'/../app/Views/market/opponents.php') ?: '';
$match=file_get_contents(__DIR__.'/../app/Views/matches/show.php') ?: '';
$team=file_get_contents(__DIR__.'/../app/Views/teams/show.php') ?: '';
$career=file_get_contents(__DIR__.'/../app/Views/career/show.php') ?: '';
$css=file_get_contents(__DIR__.'/../public/assets/css/app.css') ?: '';
foreach([[$home,'home_feed'],[$home,'home_sidebar'],[$players,'players_feed'],[$opponents,'opponents_feed'],[$match,'match_inline'],[$team,'team_inline'],[$career,'career_inline']] as [$view,$slot]){
    expect_true(str_contains($view,$slot),"View must include {$slot} ad slot.");
}
foreach(['.ad-slot','.ad-card','.ad-sponsored-label','.ad-external-frame','max-width:100%'] as $needle){
    expect_true(str_contains($css,$needle),"Responsive ad CSS must contain {$needle}.");
}
expect_true(str_contains($css,'@media (max-width:700px)'),'Ad UI must keep mobile-first responsive breakpoint.');
echo "PASS v8_ad_ui_test\n";
