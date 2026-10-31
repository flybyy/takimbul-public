<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Csrf;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\View;
use App\Models\FootballIdentity;
use App\Models\PlayerProfile;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\UserToken;
use PDOException;
use Throwable;

final class AuthController
{
    public function showRegister(): void { View::render('auth/register', ['title' => 'Üye Ol']); }

    public function register(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) { \App\Core\HttpError::render(419,'Oturum doğrulaması başarısız.');return; }
        if (trim((string)($_POST['website'] ?? '')) !== '') { http_response_code(204); return; }
        if (!RateLimiter::allow(RateLimiter::clientKey('register',(string)($_POST['email']??'')),5,3600)) { http_response_code(429); View::render('auth/register',['title'=>'Üye Ol','errors'=>['form'=>'Çok fazla kayıt denemesi. Bir süre sonra tekrar dene.'],'old'=>$_POST]); return; }
        $errors = User::validateRegistration($_POST);
        if ($errors !== []) { View::render('auth/register', ['title' => 'Üye Ol', 'errors' => $errors, 'old' => $_POST]); return; }

        $pdo = null;
        $id = 0;
        $requiresVerification = SiteSetting::get('require_email_verification','0') === '1';
        try {
            $pdo = Database::connection();
            $pdo->beginTransaction();
            $id = User::create($_POST);
            $identityTypes=FootballIdentity::normalizeTypes($_POST['identity_types'] ?? []);
            FootballIdentity::syncForUser($id,$identityTypes);
            if(in_array('recreational_player',$identityTypes,true) || in_array('amateur_player',$identityTypes,true)){
                PlayerProfile::upsert($id, ['visibility' => in_array('recreational_player',$identityTypes,true) ? 'public' : 'private']);
            }
            if (!$requiresVerification) {
                User::markEmailVerified($id);
            }
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo !== null && $pdo->inTransaction()) $pdo->rollBack();
            $sqlState = (string)$e->getCode();
            $driverCode = (int)($e->errorInfo[1] ?? 0);
            if ($sqlState === '23000' || $driverCode === 1062) {
                View::render('auth/register', ['title'=>'Üye Ol','errors'=>['form'=>'Bu e-posta, kullanıcı adı veya telefon numarası zaten başka bir hesapta kullanılıyor.'],'old'=>$_POST]);
                return;
            }
            $errorId=Logger::exception($e);
            http_response_code(503);
            View::render('auth/register', ['title'=>'Üye Ol','errors'=>['form'=>'Kayıt şu anda tamamlanamadı. Lütfen birkaç dakika sonra tekrar dene. Hata kodu: '.$errorId],'old'=>$_POST]);
            return;
        } catch (Throwable $e) {
            if ($pdo !== null && $pdo->inTransaction()) $pdo->rollBack();
            $errorId=Logger::exception($e);
            http_response_code(503);
            View::render('auth/register', ['title'=>'Üye Ol','errors'=>['form'=>'Kayıt şu anda tamamlanamadı. Lütfen birkaç dakika sonra tekrar dene. Hata kodu: '.$errorId],'old'=>$_POST]);
            return;
        }

        if ($requiresVerification) {
            try {
                $token=UserToken::issue($id,'email_verification',86400);
                $sent=Mailer::verification((string)$_POST['email'],(string)$_POST['display_name'],$token);
                $_SESSION['flash_success']=$sent
                    ? 'Hesabın oluşturuldu. E-posta adresine gönderilen doğrulama bağlantısını açtıktan sonra giriş yapabilirsin.'
                    : 'Hesabın oluşturuldu. Doğrulama e-postası gönderilemedi; giriş ekranından yeniden gönderebilirsin.';
            } catch (Throwable $e) {
                Logger::exception($e);
                $_SESSION['flash_success']='Hesabın oluşturuldu. Doğrulama e-postasını giriş ekranından yeniden gönderebilirsin.';
            }
            header('Location: /giris');
            return;
        }

        Auth::login([
            'id'=>$id,
            'display_name'=>trim((string)$_POST['display_name']),
            'email'=>strtolower(trim((string)$_POST['email'])),
            'role'=>'player',
        ]);
        header('Location: '.FootballIdentity::nextOnboardingPath($id));
    }

    public function showLogin(): void { View::render('auth/login', ['title' => 'Giriş Yap']); }

    public function login(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) { \App\Core\HttpError::render(419,'Oturum doğrulaması başarısız.');return; }
        $identifier=trim((string)($_POST['identifier']??''));
        if (!RateLimiter::allow(RateLimiter::clientKey('login',$identifier),10,900)) { http_response_code(429); View::render('auth/login',['title'=>'Giriş Yap','error'=>'Çok fazla giriş denemesi. 15 dakika sonra tekrar dene.']); return; }
        $user = User::findByIdentifier($identifier);
        if ($user === null || $user['status'] !== 'active' || ($user['role']??'player')!=='player' || !password_verify((string) ($_POST['password'] ?? ''), (string) $user['password_hash'])) { View::render('auth/login', ['title' => 'Giriş Yap', 'error' => 'E-posta/kullanıcı adı veya şifre hatalı.']); return; }
        if (SiteSetting::get('require_email_verification','0')==='1' && empty($user['email_verified_at'])) { View::render('auth/login',['title'=>'Giriş Yap','error'=>'Giriş yapmadan önce e-posta adresini doğrulamalısın.']); return; }
        Auth::login($user); header('Location: /');
    }

    public function logout(): void { if (!Csrf::validate($_POST['_csrf'] ?? null)) { \App\Core\HttpError::render(419);return; } Auth::logout(); header('Location: /'); }
}
