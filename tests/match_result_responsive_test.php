<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$css = file_get_contents(__DIR__ . '/../public/assets/css/app.css') ?: '';
$career = file_get_contents(__DIR__ . '/../app/Views/career/show.php') ?: '';

expect_true(str_contains($css, '.result-entry-form'), 'Result entry form needs dedicated product styles.');
expect_true(str_contains($css, '.result-team-columns'), 'Player stat entry should use a responsive team column layout.');
expect_true(str_contains($css, '.captain-result-actions'), 'Captain confirm/dispute controls need dedicated styles.');
expect_true(str_contains($css, '@media(max-width:700px)') && str_contains($css, '.result-team-columns{grid-template-columns:1fr}'), 'Result workflow must collapse to one column on mobile.');
expect_true(str_contains($career, 'Doğrulanmış İstatistik'), 'Career history should visibly identify verified player statistics.');

fwrite(STDOUT, "PASS match_result_responsive_test\n");
