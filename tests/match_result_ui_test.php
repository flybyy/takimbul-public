<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$index = file_get_contents(__DIR__ . '/../public/index.php') ?: '';
$controller = file_get_contents(__DIR__ . '/../app/Controllers/MatchController.php') ?: '';
$room = file_get_contents(__DIR__ . '/../app/Views/matches/show.php') ?: '';
$resultView = is_file(__DIR__ . '/../app/Views/matches/result.php') ? file_get_contents(__DIR__ . '/../app/Views/matches/result.php') : '';

foreach (['/mac/{id}/sonuc', '/mac/{id}/sonuc/onayla', '/mac/{id}/sonuc/itiraz'] as $route) {
    expect_true(str_contains($index, $route), "Result workflow route bulunmalı: {$route}");
}

foreach (['resultForm', 'submitResult', 'confirmResult', 'disputeResult'] as $method) {
    expect_true(str_contains($controller, 'function ' . $method), "MatchController::{$method} bulunmalı.");
}

expect_true(str_contains($resultView, 'Maç Sonucunu Gir'), 'Result form should clearly label the score submission action.');
expect_true(str_contains($room, 'Sonucu Onayla'), 'Match room should let the opposing captain confirm a pending result.');
expect_true(str_contains($room, 'İtiraz Et'), 'Match room should let the opposing captain dispute a pending result.');
expect_true(str_contains($room, 'Doğrulanmış İstatistik'), 'Verified matches should visibly mark player stats as verified.');

fwrite(STDOUT, "PASS match_result_ui_test\n");
