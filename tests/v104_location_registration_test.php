<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Core\TurkeyLocation;
use App\Models\User;
use App\Models\Ad;

expect_true(class_exists(TurkeyLocation::class),'TurkeyLocation core service missing.');
$provinces=TurkeyLocation::provinces();
expect_true(count($provinces)===81,'Türkiye location source must contain exactly 81 provinces.');
expect_true(in_array('Antalya',$provinces,true),'Antalya province missing.');
expect_true(in_array('Alanya',TurkeyLocation::districts('Antalya'),true),'Antalya/Alanya pair missing.');
expect_true(TurkeyLocation::isValidPair('Antalya','Alanya'),'Valid Antalya/Alanya pair rejected.');
expect_true(!TurkeyLocation::isValidPair('Antalya','Çankaya'),'Invalid Antalya/Çankaya pair accepted.');
expect_true(TurkeyLocation::districtCount()>=970,'District dataset is incomplete.');

$base=['display_name'=>'Test Oyuncu','username'=>'testoyuncu','phone'=>'05551234567','email'=>'oyuncu@example.com','password'=>'abc1234567','password_confirmation'=>'abc1234567','province'=>'Antalya','district'=>'Alanya','privacy_consent'=>'1'];
$errors=User::validateRegistration($base);
expect_true(!isset($errors['province'])&&!isset($errors['district'])&&!isset($errors['password_confirmation']),'Valid registration location/password confirmation rejected.');
$mismatch=$base;$mismatch['password_confirmation']='abc1234568';
expect_true(isset(User::validateRegistration($mismatch)['password_confirmation']),'Password confirmation mismatch must be rejected.');
$badPair=$base;$badPair['district']='Çankaya';
expect_true(isset(User::validateRegistration($badPair)['district']),'Invalid province/district pair must be rejected server-side.');

$ad=['type'=>'manual','advertiser_name'=>'Test Marka','title'=>'Test Reklam','body'=>'','target_url'=>'https://example.com','image_url'=>'','external_code'=>'','placement'=>'home_feed','device_target'=>'all','province'=>'Antalya','district'=>'Çankaya','starts_at'=>'2026-09-05 10:00:00','ends_at'=>'2026-10-05 10:00:00','status'=>'draft'];
expect_true(isset(Ad::validateInput($ad)['district']),'Ad targeting must reject an invalid province/district pair.');

echo "PASS v104_location_registration_test\n";
