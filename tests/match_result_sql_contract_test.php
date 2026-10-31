<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Models\FootballMatch;
use App\Models\MatchResult;

expect_true(method_exists(MatchResult::class, 'submit'), 'MatchResult::submit must persist a pending result proposal.');
expect_true(method_exists(MatchResult::class, 'confirm'), 'MatchResult::confirm must provide the second captain confirmation.');
expect_true(method_exists(MatchResult::class, 'dispute'), 'MatchResult::dispute must record a captain dispute.');
expect_true(method_exists(MatchResult::class, 'stateForMatch'), 'MatchResult::stateForMatch must expose pending result details.');
expect_true(method_exists(FootballMatch::class, 'captainSide'), 'FootballMatch::captainSide must resolve home/away captain authority.');

$resultSource = file_get_contents(__DIR__ . '/../app/Models/MatchResult.php');
$offerSource = file_get_contents(__DIR__ . '/../app/Models/MatchOffer.php');
$lineupSource = file_get_contents(__DIR__ . '/../app/Models/MatchLineup.php');

expect_true(str_contains($resultSource, 'FOR UPDATE'), 'Result persistence must lock the match row before transitions.');
expect_true(str_contains($resultSource, 'verified=1'), 'Player stats must be promoted to verified only during final match verification.');
expect_true(str_contains($resultSource, 'status="disputed"') || str_contains($resultSource, "status='disputed'"), 'Dispute flow must mark the match as disputed.');
expect_true(str_contains($resultSource, 'home_confirmed_at=NULL') && str_contains($resultSource, 'away_confirmed_at=NULL'), 'Dispute must clear both captain confirmations.');
expect_true(!str_contains($offerSource, 'INSERT IGNORE INTO match_players'), 'Accepted offers must not auto-promote the whole team roster into the match squad.');
expect_true(str_contains($lineupSource, 'INSERT INTO match_players'), 'Captain-selected lineups must synchronize the compatibility match_players roster.');

fwrite(STDOUT, "PASS match_result_sql_contract_test\n");
