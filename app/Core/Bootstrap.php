<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Bootstrap
{
    public static function init(): void
    {
        date_default_timezone_set((string)AppConfig::get('app.timezone','Europe/Istanbul'));
        ini_set('display_errors', AppConfig::debug() ? '1' : '0');
        ini_set('log_errors','0');
        error_reporting(E_ALL);

        $path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH) ?: '/';
        $method=strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'));
        $sessionName='takimbul_session';
        $hasSessionCookie=isset($_COOKIE[$sessionName]) && (string)$_COOKIE[$sessionName]!=='';
        $safeRead=in_array($method,['GET','HEAD'],true);
        $sessionRequired=$hasSessionCookie || !$safeRead || self::routeNeedsSession($path);
        $forwarded=strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwarded === 'https';

        if ($sessionRequired && session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.use_strict_mode','1');
            ini_set('session.use_only_cookies','1');
            session_cache_limiter('');
            session_name($sessionName);
            session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
            session_start();
        }

        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            header_remove('X-Powered-By');
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
            header('Cross-Origin-Opener-Policy: same-origin');
            header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com https://pagead2.googlesyndication.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; frame-src 'self' https://googleads.g.doubleclick.net https://tpc.googlesyndication.com https://pagead2.googlesyndication.com; connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com https://pagead2.googlesyndication.com https://googleads.g.doubleclick.net");
            if ($secure) header('Strict-Transport-Security: max-age=31536000');
            if (!$sessionRequired && $safeRead) header('Cache-Control: public, max-age=60, stale-while-revalidate=120');
            else header('Cache-Control: private, no-store, max-age=0');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['_session_started_at'])) $_SESSION['_session_started_at']=time();
            if (isset($_SESSION['user']['id']) && (!isset($_SESSION['_activity_touched_at']) || time()-(int)$_SESSION['_activity_touched_at']>=60)) {
                \App\Models\User::touchActivity((int)$_SESSION['user']['id']);
                $_SESSION['_activity_touched_at']=time();
            }
            if (!isset($_SESSION['_session_regenerated_at']) || time()-(int)$_SESSION['_session_regenerated_at']>900) {
                session_regenerate_id(true); $_SESSION['_session_regenerated_at']=time();
            }
        }

        set_exception_handler(static function(Throwable $e): void {
            $errorId=Logger::exception($e);
            if (headers_sent() === false) http_response_code(500);
            if (AppConfig::debug()) {
                echo '<pre>' . htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') . '</pre>';
                return;
            }
            $file=dirname(__DIR__).'/Views/errors/500.php';
            if (is_file($file)) require $file; else echo 'Beklenmeyen bir hata oluştu.';
        });
    }

    private static function routeNeedsSession(string $path): bool
    {
        $prefixes=['/uye-ol','/giris','/sifremi','/sifre-','/e-posta','/profil','/musait','/takim-olustur','/basvurular','/mac','/mesaj','/bildirim','/transfer','/sikayet','/admin','/scout/takip','/kariyer/vitrin','/rakip-bul/anlik','/hesabim','/takim-bul','/takim-ariyorum'];
        foreach($prefixes as $prefix) if($path===$prefix || str_starts_with($path,$prefix)) return true;
        if(str_contains($path,'/yonet')||str_contains($path,'/basvur')||str_contains($path,'/teklif')||str_contains($path,'/yeni')||str_contains($path,'/takip/')) return true;
        return false;
    }
}
