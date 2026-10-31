<?php
declare(strict_types=1);
function check(bool $ok,string $message): void { if(!$ok){fwrite(STDERR,"FAIL v103_error_surface_test\n- {$message}\n");exit(1);} }
$root=dirname(__DIR__);
foreach([403,404,419,429,500] as $code){
    $file=$root.'/app/Views/errors/'.$code.'.php';
    check(is_file($file),"{$code} error template missing.");
    $body=(string)file_get_contents($file);
    check(str_contains($body,(string)$code) || $code===500,"{$code} surface must identify the HTTP state.");
}
$http=(string)file_get_contents($root.'/app/Core/HttpError.php');
$e429=(string)file_get_contents($root.'/app/Views/errors/429.php');
$e500=(string)file_get_contents($root.'/app/Views/errors/500.php');
$css=(string)file_get_contents($root.'/public/assets/css/app.css');
check(str_contains($http,"429 => 'Çok fazla istek gönderildi'"),'HttpError 429 title mapping missing.');
check(str_contains($e429,'Biraz fazla hızlı') && str_contains($e429,'Tekrar Dene'),'429 recovery guidance missing.');
check(str_contains($e429,'error-shell') && str_contains($e429,'error-card'),'429 branded surface classes missing.');
check(str_contains($e500,'noindex,nofollow'),'500 must be noindex.');
check(str_contains($e500,'TakımBul') && str_contains($e500,'Hata kodu'),'500 branded diagnostic copy missing.');
check(str_contains($css,'.error-shell') && str_contains($css,'.error-card'),'Shared V10.3 error styling missing.');
echo "PASS v103_error_surface_test\n";
