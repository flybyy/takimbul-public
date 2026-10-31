<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$teamSrc=(string)file_get_contents(dirname(__DIR__).'/app/Models/Team.php');
$careerController=(string)file_get_contents(dirname(__DIR__).'/app/Controllers/CareerController.php');
$careerView=(string)file_get_contents(dirname(__DIR__).'/app/Views/career/show.php');
expect_true(str_contains($teamSrc,'activeMembershipsForUser'),'Team modeli oyuncunun tüm aktif takım üyeliklerini döndürmeli.');
expect_true(str_contains($teamSrc,'tm.team_position'),'Aktif takımlar takım bazlı mevkii içermeli.');
expect_true(str_contains($teamSrc,'tm.squad_status'),'Aktif takımlar kadro statüsünü içermeli.');
expect_true(str_contains($teamSrc,'tm.jersey_number'),'Aktif takımlar forma numarasını içermeli.');
expect_true(str_contains($teamSrc,'WHERE tm.user_id=:user_id') && str_contains($teamSrc,'tm.status="active"'),'Sorgu bir oyuncunun bütün aktif üyeliklerini almalı.');
expect_true(str_contains($careerController,'Team::activeMembershipsForUser'),'Kariyer controller yeni çoklu takım sorgusunu kullanmalı.');
expect_true(str_contains($careerView,'Aktif Takımlar'),'Oyuncu profilinde Aktif Takımlar başlığı bulunmalı.');
expect_true(str_contains($careerView,'team_position'),'Oyuncu profilinde takım bazlı mevki gösterilmeli.');
expect_true(!str_contains($careerView,"['phone']")&&!str_contains($careerView,"['email']"),'Aktif takım entegrasyonu public iletişim bilgisi göstermemeli.');
echo "PASS v106_player_active_teams_test\n";
