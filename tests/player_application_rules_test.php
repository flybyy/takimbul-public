<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Models\PlayerApplication;

expect_same([], PlayerApplication::validateInput(['message' => 'Maçta oynayabilirim, uygunum.']), 'Normal başvuru mesajı geçerli olmalı.');
expect_true(isset(PlayerApplication::validateInput(['message' => str_repeat('a', 301)])['message']), '300 karakterden uzun mesaj reddedilmeli.');
expect_true(PlayerApplication::canTransition('pending', 'withdrawn', 'applicant'), 'Başvuran bekleyen başvurusunu geri çekebilmeli.');
expect_true(!PlayerApplication::canTransition('pending', 'accepted', 'applicant'), 'Başvuran kendi başvurusunu kabul edememeli.');
expect_true(PlayerApplication::canTransition('pending', 'accepted', 'captain'), 'Kaptan bekleyen başvuruyu kabul edebilmeli.');
expect_true(PlayerApplication::canTransition('pending', 'rejected', 'captain'), 'Kaptan bekleyen başvuruyu reddedebilmeli.');
expect_true(!PlayerApplication::canTransition('accepted', 'rejected', 'captain'), 'Sonuçlanmış başvuru tekrar değiştirilememeli.');
expect_true(PlayerApplication::canAcceptIntoRoster(2, 1), 'Boş kontenjan varsa oyuncu kabul edilebilmeli.');
expect_true(!PlayerApplication::canAcceptIntoRoster(2, 2), 'İlan kontenjanı dolduysa yeni oyuncu kabul edilmemeli.');

echo "PASS player_application_rules_test\n";
