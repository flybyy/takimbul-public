<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\TransferOffer;

expect_same([], TransferOffer::validateInput([
    'proposed_position'=>'Orta Saha',
    'proposed_jersey_number'=>'10',
    'message'=>'Takımımıza katılmanı istiyoruz.',
]), 'Valid transfer offer must pass.');
expect_true(isset(TransferOffer::validateInput(['proposed_position'=>'Teknik Direktör'])['proposed_position']), 'Unknown position must fail.');
expect_true(isset(TransferOffer::validateInput(['proposed_jersey_number'=>'0'])['proposed_jersey_number']), 'Jersey zero must fail.');
expect_true(isset(TransferOffer::validateInput(['proposed_jersey_number'=>'100'])['proposed_jersey_number']), 'Jersey above 99 must fail.');
expect_true(isset(TransferOffer::validateInput(['message'=>str_repeat('x',501)])['message']), 'Message above 500 chars must fail.');
expect_true(TransferOffer::canTransition('pending','accepted'), 'Pending offer can be accepted.');
expect_true(TransferOffer::canTransition('pending','rejected'), 'Pending offer can be rejected.');
expect_true(TransferOffer::canTransition('pending','withdrawn'), 'Pending offer can be withdrawn.');
expect_true(!TransferOffer::canTransition('accepted','rejected'), 'Accepted offer is immutable.');

fwrite(STDOUT,"PASS transfer_offer_rules_test\n");
