<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Models\MatchOffer;

expect_true(!MatchOffer::canOffer(7, 7), 'Takım kendi ilanına maç teklifi gönderememeli.');
expect_true(MatchOffer::canOffer(7, 8), 'Farklı takım ilana teklif gönderebilmeli.');
expect_true(MatchOffer::canTransition('pending', 'accepted'), 'Bekleyen teklif kabul edilebilmeli.');
expect_true(MatchOffer::canTransition('pending', 'rejected'), 'Bekleyen teklif reddedilebilmeli.');
expect_true(MatchOffer::canTransition('pending', 'withdrawn'), 'Bekleyen teklif geri çekilebilmeli.');
expect_true(!MatchOffer::canTransition('accepted', 'rejected'), 'Kabul edilmiş teklif tekrar değiştirilememeli.');

$post = [
    'id' => 12,
    'team_id' => 3,
    'match_at' => '2026-09-05 21:00:00',
    'province' => 'Antalya',
    'district' => 'Alanya',
    'venue' => 'Oba Halı Saha',
    'match_format' => '7v7',
];
$payload = MatchOffer::matchPayload($post, 9);
expect_same(3, $payload['home_team_id'], 'İlan sahibi ev sahibi olmalı.');
expect_same(9, $payload['away_team_id'], 'Teklif gönderen deplasman takımı olmalı.');
expect_same('7v7', $payload['match_format'], 'Maç formatı ilandan aktarılmalı.');
expect_same(12, $payload['source_opponent_post_id'], 'Kaynak ilan kimliği korunmalı.');

echo "PASS match_offer_rules_test\n";
