<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\SiteSetting;

expect_same(null,SiteSetting::validate('require_email_verification','0'),'Email verification toggle 0 should be valid.');
expect_same(null,SiteSetting::validate('require_email_verification','1'),'Email verification toggle 1 should be valid.');
expect_true(SiteSetting::validate('require_email_verification','yes')!==null,'Email verification toggle must reject non-binary values.');

$auth=file_get_contents(dirname(__DIR__).'/app/Controllers/AuthController.php') ?: '';
expect_true(str_contains($auth,"SiteSetting::get('require_email_verification','0')"),'Auth must use live site setting for verification requirement.');
expect_true(!str_contains($auth,"AppConfig::get('auth.require_email_verification',true)"),'Auth must not default email verification to required.');

$login=file_get_contents(dirname(__DIR__).'/app/Views/auth/login.php') ?: '';
expect_true(str_contains($login,"SiteSetting::get('require_email_verification','0')==='1'"),'Login view must conditionally expose resend only when verification is enabled.');

$config=file_get_contents(dirname(__DIR__).'/config/app.example.php') ?: '';
expect_true(str_contains($config,"'require_email_verification' => false"),'Example config must default verification to false.');

echo "PASS v91_email_verification_toggle_test\n";
