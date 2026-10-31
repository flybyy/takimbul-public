<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Models\Conversation;
use App\Models\Notification;

expect_true(isset(Conversation::validateMessage('')['body']), 'Boş mesaj reddedilmeli.');
expect_same([], Conversation::validateMessage('Selam, maç için yazıyorum.'), 'Normal mesaj geçerli olmalı.');
expect_true(isset(Conversation::validateMessage(str_repeat('x', 2001))['body']), '2000 karakterden uzun mesaj reddedilmeli.');
expect_same([4, 18], Conversation::orderedPair(18, 4), 'Katılımcılar deterministik sıraya alınmalı.');
expect_same([4, 18], Conversation::orderedPair(4, 18), 'Katılımcı sırası giriş yönünden bağımsız olmalı.');
expect_true(isset(Conversation::validateRecipient(5, 5)['recipient']), 'Kullanıcı kendisine mesaj gönderememeli.');
expect_same([], Conversation::validateRecipient(5, 6), 'Farklı kullanıcı geçerli alıcı olmalı.');

expect_same('/mac/12', Notification::safeLink('/mac/12'), 'Site içi bildirim bağlantısı korunmalı.');
expect_same(null, Notification::safeLink('https://example.com/phish'), 'Harici bildirim bağlantısı reddedilmeli.');
expect_same(null, Notification::safeLink('//example.com/phish'), 'Protokol-relative bağlantı reddedilmeli.');

echo "PASS messaging_rules_test\n";
