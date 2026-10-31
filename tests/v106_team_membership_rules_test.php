<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\TeamMembership;

expect_same([],TeamMembership::validateSettings([
    'role'=>'player','jersey_number'=>'7','team_position'=>'Kanat','squad_status'=>'starter'
],false),'Takım bazlı mevki/statü geçerli değerlerle kabul edilmeli.');
$badPos=TeamMembership::validateSettings(['role'=>'player','team_position'=>'Teknik Direktör','squad_status'=>'reserve'],false);
expect_true(isset($badPos['team_position']),'Bilinmeyen takım mevkii reddedilmeli.');
$badStatus=TeamMembership::validateSettings(['role'=>'player','team_position'=>'Forvet','squad_status'=>'tribune'],false);
expect_true(isset($badStatus['squad_status']),'Bilinmeyen kadro statüsü reddedilmeli.');
expect_same([],TeamMembership::validateSettings(['role'=>'captain','jersey_number'=>'1','team_position'=>'Kaleci','squad_status'=>'starter'],true),'Kaptan kendi sportif ayarlarını rolünü koruyarak değiştirebilmeli.');
expect_true(isset(TeamMembership::validateSettings(['role'=>'player','team_position'=>'Kaleci','squad_status'=>'starter'],true)['role']),'Kaptan kendi rolünü sıradan üye formundan düşürememeli.');
$src=(string)file_get_contents(dirname(__DIR__).'/app/Models/TeamMembership.php');
expect_true(str_contains($src,'team_id=:team_id'),'Forma müsaitliği sorgusu takım id ile scope edilmeli.');
expect_true(str_contains($src,'team_position=:team_position'),'Üye güncellemesi takım mevkiini yazmalı.');
expect_true(str_contains($src,'squad_status=:squad_status'),'Üye güncellemesi kadro statüsünü yazmalı.');
echo "PASS v106_team_membership_rules_test\n";
