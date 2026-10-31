<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Support\VerificationBadge;
$html=VerificationBadge::render(true);
expect_true(str_contains($html,'tb-verified-badge'),'Doğrulama rozet classı olmalı.');
expect_true(str_contains($html,'TakımBul tarafından doğrulanmış profil'),'Rozet erişilebilir açıklama taşımalı.');
expect_true(!str_contains($html,'email_verified'),'Profil doğrulaması email doğrulamasıyla karışmamalı.');
expect_same('',VerificationBadge::render(false),'Doğrulanmamış profile rozet basılmamalı.');
$user=(string)file_get_contents(dirname(__DIR__).'/app/Models/User.php');
expect_true(str_contains($user,'setProfileVerified'),'User::setProfileVerified bulunmalı.');
expect_true(str_contains($user,'profile_verified_at'),'Public/admin sorgular doğrulama alanını taşımalı.');
$admin=(string)file_get_contents(dirname(__DIR__).'/app/Controllers/AdminController.php');
expect_true(str_contains($admin,"requirePermission('players.verify')"),'Mavi tik mutation permission ile korunmalı.');
expect_true(str_contains($admin,'player.verification_granted'),'Tik verme audit edilmelidir.');
expect_true(str_contains($admin,'player.verification_revoked'),'Tik kaldırma audit edilmelidir.');
$view=(string)file_get_contents(dirname(__DIR__).'/app/Views/admin/user-detail.php');
expect_true(str_contains($view,'/dogrulama'),'Kullanıcı detayında doğrulama aksiyonu olmalı.');
$publicFiles=['app/Views/amateur-market/show.php','app/Views/amateur-market/index.php','app/Views/search/index.php','app/Views/teams/show.php','app/Views/leaderboard/index.php'];
foreach($publicFiles as $file){$src=(string)file_get_contents(dirname(__DIR__).'/'.$file);expect_true(str_contains($src,'VerificationBadge::render'),$file.' reusable badge kullanmalı.');}
foreach($publicFiles as $file){$src=(string)file_get_contents(dirname(__DIR__).'/'.$file);expect_true(!str_contains($src,"['phone']")&&!str_contains($src,"['email']"),$file.' public telefon/e-posta render etmemeli.');}
echo "PASS v107_player_verification_test\n";
