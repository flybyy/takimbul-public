<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Models\Leaderboard;

$rows = [
 ['user_id'=>1,'display_name'=>'A','province'=>'Antalya','district'=>'Alanya','match_id'=>10,'final_rating'=>8.2,'goals'=>2,'assists'=>1,'is_mvp'=>1,'match_status'=>'verified','stats_verified'=>1],
 ['user_id'=>1,'display_name'=>'A','province'=>'Antalya','district'=>'Alanya','match_id'=>11,'final_rating'=>7.8,'goals'=>0,'assists'=>2,'is_mvp'=>0,'match_status'=>'verified','stats_verified'=>1],
 ['user_id'=>2,'display_name'=>'B','province'=>'İzmir','district'=>'Bornova','match_id'=>12,'final_rating'=>9.0,'goals'=>3,'assists'=>0,'is_mvp'=>1,'match_status'=>'verified','stats_verified'=>1],
 ['user_id'=>3,'display_name'=>'C','province'=>'Antalya','district'=>'Alanya','match_id'=>13,'final_rating'=>10.0,'goals'=>5,'assists'=>5,'is_mvp'=>1,'match_status'=>'score_pending','stats_verified'=>1],
];
$ranked = Leaderboard::aggregateRows($rows, 'rating');
expect_same(2, count($ranked), 'Only verified matches should reach leaderboard.');
expect_same(2, $ranked[0]['user_id'], 'Highest average rating should rank first.');
expect_same(8.0, $ranked[1]['rating'], 'Average rating should be rounded to one decimal.');
expect_same(2, $ranked[1]['matches'], 'Matches should aggregate per player.');
expect_same(3, $ranked[1]['assists'], 'Assists should aggregate per player.');

$goals = Leaderboard::aggregateRows($rows, 'goals');
expect_same(2, $goals[0]['user_id'], 'Goals sort should rank highest goals first.');

$ant = Leaderboard::filterScope($rows, 'Antalya', 'Alanya');
expect_same(3, count($ant), 'Scope filter should retain matching location rows before verified aggregation.');

fwrite(STDOUT, "PASS leaderboard_rules_test\n");
