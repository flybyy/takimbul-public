<?php

declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$routes=file_get_contents(__DIR__.'/../public/index.php') ?: '';
$show=file_get_contents(__DIR__.'/../app/Views/matches/show.php') ?: '';
$lineup=is_file(__DIR__.'/../app/Views/matches/lineup.php') ? (file_get_contents(__DIR__.'/../app/Views/matches/lineup.php') ?: '') : '';
$performance=is_file(__DIR__.'/../app/Views/matches/performance.php') ? (file_get_contents(__DIR__.'/../app/Views/matches/performance.php') ?: '') : '';
$ranking=is_file(__DIR__.'/../app/Views/leaderboard/index.php') ? (file_get_contents(__DIR__.'/../app/Views/leaderboard/index.php') ?: '') : '';
$css=file_get_contents(__DIR__.'/../public/assets/css/app.css') ?: '';
foreach(['/mac/{id}/kadro','/mac/{id}/performans','/siralama'] as $needle) expect_true(str_contains($routes,$needle),"Route {$needle} must exist.");
expect_true(str_contains($lineup,'İlk'), 'Lineup view must expose starters section.');
expect_true(str_contains($lineup,'Yedekler'), 'Lineup view must expose substitutes section.');
expect_true(str_contains($performance,'Maç Puanı'), 'Performance view must expose match rating controls.');
expect_true(str_contains($ranking,'Sezon Sıralaması'), 'Leaderboard view must expose season ranking.');
expect_true(str_contains($show,'Kadrom') && str_contains($show,'Performans'), 'Match room must link lineup and performance flows.');
expect_true(str_contains($css,'.lineup-page') && str_contains($css,'.leaderboard-page') && str_contains($css,'@media(max-width:700px)'), 'v5 pages must include mobile responsive styles.');
fwrite(STDOUT,"PASS v5_ui_test\n");
