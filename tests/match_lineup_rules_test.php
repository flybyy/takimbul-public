<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Models\MatchLineup;

expect_same(5, MatchLineup::starterLimit('5v5'), '5v5 must require five starters.');
expect_same(7, MatchLineup::starterLimit('7v7'), '7v7 must require seven starters.');
expect_same(8, MatchLineup::starterLimit('8v8'), '8v8 must require eight starters.');

$members = [11,12,13,14,15,16,17,18,19,20];
$valid = ['starters' => [11,12,13,14,15,16,17], 'substitutes' => [18,19]];
expect_same([], MatchLineup::validateSelection($valid, $members, '7v7'), 'A valid first seven and substitutes must pass.');

$short = $valid;
$short['starters'] = [11,12,13,14,15,16];
expect_true(isset(MatchLineup::validateSelection($short, $members, '7v7')['starters']), '7v7 must require exactly seven starters.');

$duplicate = $valid;
$duplicate['substitutes'][] = 11;
expect_true(isset(MatchLineup::validateSelection($duplicate, $members, '7v7')['players']), 'A player cannot be starter and substitute.');

$foreign = $valid;
$foreign['substitutes'][] = 999;
expect_true(isset(MatchLineup::validateSelection($foreign, $members, '7v7')['players']), 'Foreign team members must be rejected.');

$tooManySubs = $valid;
$tooManySubs['substitutes'] = [18,19,20,21,22,23,24,25];
expect_true(isset(MatchLineup::validateSelection($tooManySubs, array_merge($members,[21,22,23,24,25]), '7v7')['substitutes']), 'Substitute count must be capped at seven.');

expect_true(MatchLineup::canEdit('confirmed', true, 2_000, 1_999), 'Captain may edit lineup before kickoff.');
expect_true(!MatchLineup::canEdit('confirmed', true, 2_000, 2_000), 'Lineup locks at kickoff.');
expect_true(!MatchLineup::canEdit('score_pending', true, 2_000, 1_900), 'Pending score state cannot edit lineup.');
expect_true(!MatchLineup::canEdit('confirmed', false, 2_000, 1_900), 'Non-captain cannot edit lineup.');

$completeRows = [];
foreach ([11,12,13,14,15,16,17] as $id) $completeRows[]=['user_id'=>$id,'team_id'=>101,'role'=>'starter'];
foreach ([21,22,23,24,25,26,27] as $id) $completeRows[]=['user_id'=>$id,'team_id'=>202,'role'=>'starter'];
expect_true(MatchLineup::isCompleteRows($completeRows,101,202,'7v7'), 'Both teams need seven starters before result entry.');
$completeRows[0]['role']='substitute';
expect_true(!MatchLineup::isCompleteRows($completeRows,101,202,'7v7'), 'Missing a starter makes the match lineup incomplete.');

fwrite(STDOUT, "PASS match_lineup_rules_test\n");
