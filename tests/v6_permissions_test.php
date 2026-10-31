<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$market=file_get_contents(__DIR__.'/../app/Controllers/MarketController.php') ?: '';
$matchOffer=file_get_contents(__DIR__.'/../app/Models/MatchOffer.php') ?: '';
$applications=file_get_contents(__DIR__.'/../app/Models/PlayerApplication.php') ?: '';
$lineup=file_get_contents(__DIR__.'/../app/Models/MatchLineup.php') ?: '';
$result=file_get_contents(__DIR__.'/../app/Models/MatchResult.php') ?: '';
expect_true(str_contains($market,'Team::canManageSporting'), 'Market creation must use sporting manager permission.');
expect_true(substr_count($matchOffer,'vice_captain')>=5, 'All match offer management paths must allow vice captain.');
expect_true(substr_count($applications,'vice_captain')>=2, 'Application list/respond paths must allow vice captain.');
expect_true(str_contains($lineup,'vice_captain'), 'Lineup SQL must allow vice captain.');
expect_true(str_contains($result,'managerSide'), 'Match result authorization must use manager side.');
fwrite(STDOUT,"PASS v6_permissions_test\n");
