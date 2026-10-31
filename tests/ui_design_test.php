<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$header = file_get_contents(__DIR__ . '/../app/Views/layouts/header.php');
$footer = file_get_contents(__DIR__ . '/../app/Views/layouts/footer.php');
$home = file_get_contents(__DIR__ . '/../app/Views/home.php');
$players = file_get_contents(__DIR__ . '/../app/Views/market/players.php');
$opponents = file_get_contents(__DIR__ . '/../app/Views/market/opponents.php');
$css = file_get_contents(__DIR__ . '/../public/assets/css/app.css');

expect_true(str_contains($header, 'class="brand-wordmark"'), 'Header should use a typography-only takimbul wordmark');
expect_true(!str_contains($header, 'brand-mark'), 'Header should not render a pictorial brand mark');
expect_true(str_contains($footer, 'class="mobile-bottom-nav"'), 'Layout should include thumb-friendly mobile bottom navigation');
expect_true(str_contains($home, 'home-match-board'), 'Homepage should include the nearby match board from the approved UI');
expect_true(str_contains($home, 'home-request-grid'), 'Homepage should include active player request cards');
expect_true(str_contains($home, 'home-team-grid'), 'Homepage should include featured team cards');
expect_true(str_contains($home, 'home-career-card'), 'Homepage should include the career profile card');
expect_true(str_contains($players, 'market-switcher'), 'Player search should include the mobile-friendly player/rival switcher');
expect_true(str_contains($opponents, 'market-switcher'), 'Opponent search should include the mobile-friendly player/rival switcher');
expect_true(str_contains($css, '.mobile-bottom-nav'), 'Styles should define the mobile bottom navigation');
expect_true(str_contains($css, '@media(max-width:700px)'), 'Styles should include a dedicated mobile breakpoint');
expect_true(str_contains($css, 'padding-bottom:calc(84px + env(safe-area-inset-bottom))'), 'Mobile layout should reserve safe-area space for the bottom navigation');

echo "PASS ui_design_test\n";
