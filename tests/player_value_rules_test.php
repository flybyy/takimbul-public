<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\PlayerValue;

$elite = PlayerValue::calculate([
    'season_rating'=>10.0,
    'season_matches'=>20,
    'goals'=>20,
    'assists'=>10,
    'season_mvps'=>5,
    'days_since_last_match'=>5,
]);
expect_same(100, $elite['score'], 'Maximum verified inputs must yield 100.');
expect_same('Elit Form', $elite['label'], '100 value must use elite label.');

$mid = PlayerValue::calculate([
    'season_rating'=>7.5,
    'season_matches'=>10,
    'goals'=>5,
    'assists'=>5,
    'season_mvps'=>1,
    'days_since_last_match'=>20,
]);
expect_true($mid['score'] >= 50 && $mid['score'] <= 80, 'Balanced player must land in mid/high range.');
expect_same(30.0, $mid['breakdown']['performance'], '7.5 rating must contribute 30 performance points.');
expect_same(10.0, $mid['breakdown']['consistency'], '10 matches must contribute 10 consistency points.');

$empty = PlayerValue::calculate([]);
expect_same(0, $empty['score'], 'No verified data must yield zero value.');
expect_same('Veri Bekleniyor', $empty['label'], 'Zero value must indicate missing data.');

fwrite(STDOUT,"PASS player_value_rules_test\n");
