<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$index = file_get_contents(__DIR__ . '/../public/index.php') ?: '';
$footer = file_get_contents(__DIR__ . '/../app/Views/layouts/footer.php') ?: '';
$schema = file_get_contents(__DIR__ . '/../database/schema.sql') ?: '';

foreach (['/basvurular', '/mac-teklifleri', '/maclar', '/mesajlar', '/bildirimler'] as $route) {
    expect_true(str_contains($index, $route), "Route bulunmalı: {$route}");
}
expect_true(str_contains($footer, 'href="/maclar"'), 'Mobil Maçlar sekmesi gerçek maç listesine gitmeli.');
expect_true(str_contains($footer, 'href="/mesajlar"'), 'Mobil menüde Mesajlar olmalı.');
expect_true(str_contains($schema, 'CREATE TABLE IF NOT EXISTS direct_threads'), 'Direct thread tablosu şemada olmalı.');
expect_true(str_contains($schema, 'CREATE TABLE IF NOT EXISTS direct_messages'), 'Direct messages tablosu şemada olmalı.');
expect_true(str_contains($schema, 'source_opponent_post_id'), 'Maç tablosu kaynak rakip ilanını saklamalı.');

echo "PASS social_ui_test\n";
