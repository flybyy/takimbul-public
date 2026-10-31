<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Models\OpponentPost;
use App\Models\PlayerWantedPost;

$future = date('Y-m-d\TH:i', time() + 86400);
$past = date('Y-m-d\TH:i', time() - 3600);

$playerErrors = PlayerWantedPost::validateInput([
    'match_at' => $past,
    'province' => 'Antalya',
    'district' => 'Alanya',
    'position' => 'Forvet',
    'players_needed' => '0',
    'level' => 'Profesyonel',
]);
expect_true(isset($playerErrors['match_at']), 'Player-needed match date must be in the future');
expect_true(isset($playerErrors['players_needed']), 'Players needed must be positive');
expect_true(isset($playerErrors['level']), 'Unknown level should be rejected');

$validPlayer = PlayerWantedPost::validateInput([
    'match_at' => $future,
    'province' => 'Antalya',
    'district' => 'Alanya',
    'position' => 'Kaleci',
    'players_needed' => '1',
    'level' => 'Orta',
]);
expect_same([], $validPlayer, 'Valid player-needed post should pass');

$opponentErrors = OpponentPost::validateInput([
    'match_at' => $future,
    'province' => 'İstanbul',
    'district' => 'Kadıköy',
    'level' => 'Orta',
    'match_format' => '11v11',
]);
expect_true(isset($opponentErrors['match_format']), 'Unsupported match format should be rejected');

$validOpponent = OpponentPost::validateInput([
    'match_at' => $future,
    'province' => 'İstanbul',
    'district' => 'Kadıköy',
    'level' => 'Rekabetçi',
    'match_format' => '7v7',
]);
expect_same([], $validOpponent, 'Valid opponent post should pass');

echo "PASS market_validation_test\n";
