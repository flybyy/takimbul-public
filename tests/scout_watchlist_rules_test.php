<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\ScoutWatchlist;

expect_true(ScoutWatchlist::canUseFromStatus('approved'), 'Approved scout can use watchlist.');
expect_true(!ScoutWatchlist::canUseFromStatus('pending'), 'Pending scout cannot use watchlist.');
expect_true(!ScoutWatchlist::canUseFromStatus('rejected'), 'Rejected scout cannot use watchlist.');
expect_true(!ScoutWatchlist::canUseFromStatus(null), 'Normal account cannot use watchlist.');


$code=file_get_contents(__DIR__.'/../app/Models/ScoutWatchlist.php') ?: '';
expect_true(!str_contains($code,'u.email') && !str_contains($code,'u.phone'), 'Watchlist queries must not expose player email or phone.');

fwrite(STDOUT,"PASS scout_watchlist_rules_test\n");
