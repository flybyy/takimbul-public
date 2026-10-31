<?php

declare(strict_types=1);
namespace App\Core;
final class Mailer
{
    public static function send(string $to,string $subject,string $html): bool
    {
        $from=(string)AppConfig::get('mail.from','noreply@takimbul.com.tr');
        $name=(string)AppConfig::get('mail.from_name','TakımBul');
        $headers=[
            'MIME-Version: 1.0','Content-Type: text/html; charset=UTF-8',
            'From: '.$name.' <'.$from.'>','Reply-To: '.$from,'X-Mailer: TakimBul'
        ];
        $ok=@mail($to,'=?UTF-8?B?'.base64_encode($subject).'?=',$html,implode("\r\n",$headers));
        if(!$ok) Logger::error('E-posta gönderilemedi',['to'=>$to,'subject'=>$subject]);
        return $ok;
    }

    public static function verification(string $to,string $name,string $token): bool
    {
        $url=AppConfig::appUrl().'/e-posta-dogrula?token='.rawurlencode($token);
        return self::send($to,'TakımBul e-posta doğrulama','<h2>Merhaba '.htmlspecialchars($name).'</h2><p>TakımBul hesabını doğrulamak için aşağıdaki bağlantıya tıkla.</p><p><a href="'.htmlspecialchars($url).'">E-posta adresimi doğrula</a></p><p>Bu bağlantı 24 saat geçerlidir.</p>');
    }

    public static function passwordReset(string $to,string $name,string $token): bool
    {
        $url=AppConfig::appUrl().'/sifre-sifirla?token='.rawurlencode($token);
        return self::send($to,'TakımBul şifre sıfırlama','<h2>Merhaba '.htmlspecialchars($name).'</h2><p>Şifreni sıfırlamak için aşağıdaki bağlantıyı kullan.</p><p><a href="'.htmlspecialchars($url).'">Şifremi sıfırla</a></p><p>Bu bağlantı 60 dakika geçerlidir. Bu isteği sen yapmadıysan e-postayı yok say.</p>');
    }
}
