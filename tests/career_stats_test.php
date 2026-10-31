<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Models\Career;

$summary = Career::aggregateRows([
    ['goals' => 2, 'assists' => 1, 'verified' => 1, 'match_status' => 'verified'],
    ['goals' => 1, 'assists' => 2, 'verified' => 1, 'match_status' => 'verified'],
    ['goals' => 9, 'assists' => 9, 'verified' => 0, 'match_status' => 'verified'],
    ['goals' => 4, 'assists' => 0, 'verified' => 1, 'match_status' => 'cancelled'],
    ['goals' => 7, 'assists' => 4, 'verified' => 1, 'match_status' => 'score_pending'],
]);
expect_same(2, $summary['matches'], 'Only verified completed matches count');
expect_same(3, $summary['goals'], 'Only verified goals count');
expect_same(3, $summary['assists'], 'Only verified assists count');
expect_same(1.5, $summary['goal_per_match'], 'Goal per match should be calculated');
expect_same(3.0, $summary['contribution_per_match'], 'Contribution per match should be goals plus assists divided by matches');

$empty = Career::aggregateRows([]);
expect_same(0, $empty['matches'], 'Empty career has zero matches');
expect_same(0.0, $empty['goal_per_match'], 'Zero-match career must not divide by zero');

echo "PASS career_stats_test\n";
