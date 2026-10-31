<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Models\MatchResult;

$players = [
    ['user_id' => 11, 'team_id' => 101],
    ['user_id' => 12, 'team_id' => 101],
    ['user_id' => 21, 'team_id' => 202],
    ['user_id' => 22, 'team_id' => 202],
];

$valid = [
    'home_team_id' => 101,
    'away_team_id' => 202,
    'home_score' => '3',
    'away_score' => '2',
    'stats' => [
        11 => ['goals' => '2', 'assists' => '1'],
        12 => ['goals' => '1', 'assists' => '1'],
        21 => ['goals' => '2', 'assists' => '1'],
        22 => ['goals' => '0', 'assists' => '0'],
    ],
];

expect_same([], MatchResult::validatePayload($valid, $players), 'Valid score and player stats should pass validation.');

$foreignPlayer = $valid;
$foreignPlayer['stats'][999] = ['goals' => '1', 'assists' => '0'];
expect_true(isset(MatchResult::validatePayload($foreignPlayer, $players)['stats']), 'A player outside the match roster must be rejected.');

$tooManyGoals = $valid;
$tooManyGoals['stats'][11]['goals'] = '4';
expect_true(isset(MatchResult::validatePayload($tooManyGoals, $players)['home_stats']), 'Team player goals cannot exceed the submitted team score.');

$tooManyAssists = $valid;
$tooManyAssists['stats'][11] = ['goals' => '2', 'assists' => '3'];
expect_true(isset(MatchResult::validatePayload($tooManyAssists, $players)['home_stats']), 'Team assists cannot exceed team goals.');

$negative = $valid;
$negative['home_score'] = '-1';
expect_true(isset(MatchResult::validatePayload($negative, $players)['home_score']), 'Negative scores must be rejected.');

expect_true(!MatchResult::canSubmit('confirmed', true, 2_000, 1_999), 'A result cannot be submitted before kickoff time.');
expect_true(MatchResult::canSubmit('confirmed', true, 2_000, 2_000), 'A captain can submit when the match time has arrived.');
expect_true(MatchResult::canSubmit('disputed', true, 2_000, 2_500), 'A disputed result can be corrected by a captain.');
expect_true(!MatchResult::canSubmit('score_pending', true, 2_000, 2_500), 'A pending result cannot be silently replaced before a dispute.');
expect_true(!MatchResult::canSubmit('verified', true, 2_000, 2_500), 'A verified result is immutable.');
expect_true(!MatchResult::canSubmit('confirmed', false, 2_000, 2_500), 'A non-captain cannot submit a result.');

expect_same('score_pending', MatchResult::nextVerificationStatus('2026-08-31 16:00:00', null), 'One confirmation keeps the result pending.');
expect_same('verified', MatchResult::nextVerificationStatus('2026-08-31 16:00:00', '2026-08-31 16:02:00'), 'Two confirmations verify the match.');

expect_true(MatchResult::canConfirm('score_pending', false, true), 'The opposing captain can confirm a pending proposal.');
expect_true(!MatchResult::canConfirm('score_pending', true, true), 'The proposing captain cannot provide the second confirmation.');
expect_true(!MatchResult::canConfirm('verified', false, true), 'A verified match cannot be confirmed again.');

fwrite(STDOUT, "PASS match_result_rules_test\n");
