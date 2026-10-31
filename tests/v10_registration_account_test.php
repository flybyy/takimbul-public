<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\User;

expect_true(method_exists(User::class,'normalizePhone'),'User::normalizePhone missing.');
expect_same('+905551234567', User::normalizePhone('0555 123 45 67'), 'TR phone must normalize to E.164-like +90 form.');
expect_same('+905551234567', User::normalizePhone('+90 (555) 123-45-67'), 'Already international TR phone must normalize.');
expect_same(null, User::normalizePhone('0212 555 11 22'), 'Landline must not pass mobile validation.');
$errors=User::validateRegistration(['display_name'=>'Test User','username'=>'testuser10','phone'=>'','email'=>'u10@example.com','password'=>'Strongpass10','province'=>'Antalya','district'=>'Alanya','privacy_consent'=>'1']);
expect_true(isset($errors['phone']),'Phone must be required at registration.');
$errors=User::validateRegistration(['display_name'=>'Test User','username'=>'testuser10','phone'=>'05551234567','email'=>'u10@example.com','password'=>'Strongpass10','province'=>'Antalya','district'=>'Alanya']);
expect_true(isset($errors['privacy_consent']),'Privacy consent must be required.');

$root=dirname(__DIR__);
$register=file_get_contents($root.'/app/Views/auth/register.php')?:'';
expect_true(str_contains($register,'name="phone"'),'Register form must ask for phone.');
expect_true(str_contains($register,'name="privacy_consent"'),'Register form must ask for privacy consent.');
$index=file_get_contents($root.'/public/index.php')?:'';
expect_true(str_contains($index,"'/hesabim'"),'Account center route missing.');
expect_true(is_file($root.'/app/Views/account/index.php'),'Account center view missing.');
echo "PASS v10_registration_account_test\n";
