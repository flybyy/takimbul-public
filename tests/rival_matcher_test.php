<?php
declare(strict_types=1);require __DIR__.'/bootstrap.php';
use App\Models\OpponentMatcher;
expect_true(class_exists(OpponentMatcher::class),'OpponentMatcher model must exist');
$team=['province'=>'Antalya','district'=>'Alanya'];
$perfect=['province'=>'Antalya','district'=>'Alanya','match_format'=>'7v7','level'=>'Orta','match_at'=>'2026-09-01 21:00:00'];
$far=['province'=>'İstanbul','district'=>'Kadıköy','match_format'=>'5v5','level'=>'Rekabetçi','match_at'=>'2026-09-05 21:00:00'];
$score=OpponentMatcher::score($team,$perfect,['match_format'=>'7v7','level'=>'Orta','target_at'=>'2026-09-01 20:30:00']);
$low=OpponentMatcher::score($team,$far,['match_format'=>'7v7','level'=>'Orta','target_at'=>'2026-09-01 20:30:00']);
expect_true($score>=90 && $score<=100,'near exact rival should score at least 90');
expect_true($low<$score,'distant incompatible rival should score lower');
expect_true(OpponentMatcher::label(94)==='Çok uyumlu','94 should be very compatible');
expect_true(OpponentMatcher::label(62)==='Uygun','62 should be compatible');
echo "PASS rival_matcher_test\n";
