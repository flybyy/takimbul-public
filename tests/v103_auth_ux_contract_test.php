<?php
declare(strict_types=1);
function must(bool $ok,string $message): void { if(!$ok){fwrite(STDERR,"FAIL v103_auth_ux_contract_test\n- {$message}\n");exit(1);} }
$register=(string)file_get_contents(__DIR__.'/../app/Views/auth/register.php');
$login=(string)file_get_contents(__DIR__.'/../app/Views/auth/login.php');
$reset=(string)file_get_contents(__DIR__.'/../app/Views/auth/reset.php');
$js=(string)file_get_contents(__DIR__.'/../public/assets/js/app.js');
$css=(string)file_get_contents(__DIR__.'/../public/assets/css/app.css');
foreach([$register,$login,$reset] as $view){
    must(str_contains($view,'data-submit-guard'),'Auth form submit guard marker missing.');
}
must(substr_count($register,'data-password-toggle')>=1,'Register password toggle missing.');
must(substr_count($login,'data-password-toggle')>=1,'Login password toggle missing.');
must(substr_count($reset,'data-password-toggle')>=2,'Reset password toggles missing.');
must(str_contains($register,'role="alert"') && str_contains($register,'aria-live="polite"'),'Accessible registration error summary missing.');
must(str_contains($login,'role="alert"') && str_contains($login,'aria-live="polite"'),'Accessible login error summary missing.');
must(str_contains($register,'data-busy-text="Hesap Oluşturuluyor…"'),'Register busy copy missing.');
must(str_contains($login,'data-busy-text="Giriş Yapılıyor…"'),'Login busy copy missing.');
foreach(['data-password-toggle','data-submit-guard','is-submitting'] as $needle){must(str_contains($js,$needle),'Auth JS behavior missing: '.$needle);}
foreach(['.password-field','.password-toggle','.is-submitting'] as $needle){must(str_contains($css,$needle),'Auth CSS behavior missing: '.$needle);}
echo "PASS v103_auth_ux_contract_test\n";
