<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\TransferOffer;

$resolved=TransferOffer::resolveAcceptedMembership([
    'proposed_position'=>'Forvet','proposed_jersey_number'=>10
],true);
expect_same('Forvet',$resolved['team_position'],'Teklif mevkii takım mevkiine aktarılmalı.');
expect_same(10,$resolved['jersey_number'],'Müsait forma korunmalı.');
expect_same('reserve',$resolved['squad_status'],'Yeni üyelik reserve başlamalı.');
expect_same(null,$resolved['jersey_warning'],'Müsait formada uyarı olmamalı.');

$occupied=TransferOffer::resolveAcceptedMembership([
    'proposed_position'=>'Kanat','proposed_jersey_number'=>7
],false);
expect_same(null,$occupied['jersey_number'],'Dolan forma üyeliği engellemek yerine boş bırakılmalı.');
expect_true(is_string($occupied['jersey_warning']) && $occupied['jersey_warning']!=='','Dolan forma için uyarı dönmeli.');

$src=(string)file_get_contents(dirname(__DIR__).'/app/Models/TransferOffer.php');
expect_true(str_contains($src,'ON DUPLICATE KEY UPDATE'),'Kabul yalnız hedef takım üyeliğine upsert yapmalı.');
expect_true(!str_contains($src,'UPDATE team_members SET status="left"'),'Başka takım üyelikleri kapatılmamalı.');
expect_true(str_contains($src,'team_position'),'Kabul team_position yazmalı.');
expect_true(str_contains($src,'squad_status'),'Kabul squad_status yazmalı.');
echo "PASS v106_transfer_multiteam_test\n";
