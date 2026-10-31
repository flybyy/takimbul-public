<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Core\Csrf;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\View;
use App\Models\User;
use App\Models\SiteSetting;
use App\Models\UserToken;
use Throwable;
final class AccountSecurityController
{
    public function forgotForm(): void { View::render('auth/forgot',['title'=>'Şifremi Unuttum']); }
    public function forgot(): void
    {
        if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $email=strtolower(trim((string)($_POST['email']??'')));
        if(!RateLimiter::allow(RateLimiter::clientKey('forgot',$email),5,3600)){http_response_code(429);View::render('auth/forgot',['title'=>'Şifremi Unuttum','message'=>'Çok fazla istek gönderildi. Bir süre sonra tekrar dene.']);return;}
        try{$user=User::findByEmail($email);if($user){$token=UserToken::issue((int)$user['id'],'password_reset',3600);Mailer::passwordReset($user['email'],$user['display_name'],$token);}}catch(Throwable){}
        View::render('auth/forgot',['title'=>'Şifremi Unuttum','message'=>'Bu e-posta kayıtlıysa şifre sıfırlama bağlantısı gönderildi.']);
    }
    public function resetForm(): void
    {
        $token=(string)($_GET['token']??'');$valid=null;try{$valid=UserToken::findValid($token,'password_reset');}catch(Throwable){}
        View::render('auth/reset',['title'=>'Yeni Şifre','token'=>$token,'valid'=>$valid!==null]);
    }
    public function reset(): void
    {
        if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $token=(string)($_POST['token']??'');$password=(string)($_POST['password']??'');$confirm=(string)($_POST['password_confirm']??'');
        $valid=User::validatePassword($password);
        $row=UserToken::findValid($token,'password_reset');
        if($row===null || $valid!==null || $password!==$confirm){View::render('auth/reset',['title'=>'Yeni Şifre','token'=>$token,'valid'=>$row!==null,'error'=>$valid??($password!==$confirm?'Şifreler eşleşmiyor.':'Bağlantı geçersiz veya süresi dolmuş.')]);return;}
        User::updatePassword((int)$row['user_id'],$password);UserToken::consume((int)$row['id']);$_SESSION['flash_success']='Şifren güncellendi. Giriş yapabilirsin.';header('Location: /giris');
    }

    public function resendForm(): void { if(SiteSetting::get('require_email_verification','0')!=='1'){$_SESSION['flash_success']='E-posta doğrulaması şu anda zorunlu değil.';header('Location: /giris');return;} View::render('auth/resend-verification',['title'=>'Doğrulama E-postası']); }
    public function resend(): void
    {
        if(SiteSetting::get('require_email_verification','0')!=='1'){$_SESSION['flash_success']='E-posta doğrulaması şu anda zorunlu değil.';header('Location: /giris');return;}
        if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $email=strtolower(trim((string)($_POST['email']??'')));
        if(!RateLimiter::allow(RateLimiter::clientKey('verify_resend',$email),3,3600)){http_response_code(429);View::render('auth/resend-verification',['title'=>'Doğrulama E-postası','message'=>'Çok fazla istek gönderildi. Bir süre sonra tekrar dene.']);return;}
        try{
            $user=User::findByEmail($email);
            if($user && empty($user['email_verified_at'])){$token=UserToken::issue((int)$user['id'],'email_verification',86400);Mailer::verification($user['email'],$user['display_name'],$token);}
        }catch(Throwable){}
        View::render('auth/resend-verification',['title'=>'Doğrulama E-postası','message'=>'Bu e-posta kayıtlı ve doğrulanmamışsa yeni bağlantı gönderildi.']);
    }

    public function verify(): void
    {
        $token=(string)($_GET['token']??'');$row=UserToken::findValid($token,'email_verification');
        if($row===null){http_response_code(400);View::render('auth/verify',['title'=>'E-posta Doğrulama','ok'=>false]);return;}
        User::markEmailVerified((int)$row['user_id']);UserToken::consume((int)$row['id']);View::render('auth/verify',['title'=>'E-posta Doğrulandı','ok'=>true]);
    }
}
