<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Models\MatchRating;

$lineup = [11,12,13];
expect_same([], MatchRating::validateCaptainScores([11=>'8.0',12=>'7.5',13=>'6.0'], $lineup), 'Valid captain ratings should pass.');
expect_true(isset(MatchRating::validateCaptainScores([11=>'10.1',12=>'7',13=>'6'], $lineup)['scores']), 'Ratings above 10 must fail.');
expect_true(isset(MatchRating::validateCaptainScores([11=>'8',12=>'7'], $lineup)['scores']), 'Every lineup player must receive a captain score.');
expect_true(isset(MatchRating::validateCaptainScores([11=>'8',12=>'7',13=>'6',99=>'9'], $lineup)['scores']), 'Foreign player ratings must fail.');

expect_same(8.0, MatchRating::contributionScore(2, 1, 3, 1, 'starter'), 'Contribution formula must reward goals assists win and starter role.');
expect_same(7.5, MatchRating::finalRating(8.0, 6.5), 'Final rating must weight captain rating at 65 percent.');
expect_same(10.0, MatchRating::finalRating(10.0, 10.0), 'Final rating must cap at 10.');

$rows = [
    ['user_id'=>11,'final_rating'=>8.5,'goals'=>1,'assists'=>0,'role'=>'starter'],
    ['user_id'=>12,'final_rating'=>8.5,'goals'=>2,'assists'=>0,'role'=>'substitute'],
    ['user_id'=>13,'final_rating'=>8.5,'goals'=>2,'assists'=>1,'role'=>'substitute'],
];
expect_same(13, MatchRating::selectMvpUserId($rows), 'MVP tie must resolve by goals then assists.');

$starterTie = [
    ['user_id'=>20,'final_rating'=>9.0,'goals'=>1,'assists'=>1,'role'=>'substitute'],
    ['user_id'=>21,'final_rating'=>9.0,'goals'=>1,'assists'=>1,'role'=>'starter'],
];
expect_same(21, MatchRating::selectMvpUserId($starterTie), 'Starter wins an otherwise exact MVP tie.');
expect_true(MatchRating::canRate('verified', true), 'Captain can rate a verified match.');
expect_true(!MatchRating::canRate('score_pending', true), 'Unverified match cannot be rated.');
expect_true(!MatchRating::canRate('verified', false), 'Non-captain cannot rate.');

fwrite(STDOUT, "PASS match_rating_rules_test\n");
