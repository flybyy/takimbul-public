<?php
declare(strict_types=1);require __DIR__.'/bootstrap.php';
use App\Models\MatchAttendance;
expect_true(class_exists(MatchAttendance::class),'MatchAttendance model must exist');
expect_same(null,MatchAttendance::validateResponse('yes'),'yes response should be valid');
expect_same(null,MatchAttendance::validateResponse('maybe'),'maybe response should be valid');
expect_true(MatchAttendance::validateResponse('late')!==null,'unknown response should fail');
expect_true(MatchAttendance::canRespond('confirmed',strtotime('2026-09-01 21:00:00'),strtotime('2026-09-01 19:00:00')),'member can respond before match');
expect_true(!MatchAttendance::canRespond('verified',strtotime('2026-09-01 21:00:00'),strtotime('2026-09-01 19:00:00')),'verified match is locked');
expect_true(!MatchAttendance::canRespond('confirmed',strtotime('2026-09-01 21:00:00'),strtotime('2026-09-01 21:00:01')),'attendance locks at kickoff');
expect_same('matchday',MatchAttendance::matchdayMode('confirmed',strtotime('2026-09-01 21:00:00'),strtotime('2026-09-01 18:00:00')),'within six hours should be matchday');
expect_same('pre_match',MatchAttendance::matchdayMode('confirmed',strtotime('2026-09-02 21:00:00'),strtotime('2026-09-01 18:00:00')),'future match should be pre-match');
expect_same('completed',MatchAttendance::matchdayMode('verified',strtotime('2026-09-01 21:00:00'),strtotime('2026-09-02 18:00:00')),'verified is completed');
echo "PASS match_attendance_rules_test\n";
