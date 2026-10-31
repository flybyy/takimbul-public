<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$user=file_get_contents($root.'/app/Models/User.php')?:'';
foreach(['findPublic','publicPlayers'] as $method){
    $start=strpos($user,'public static function '.$method);
    $end=strpos($user,'public static function ',$start===false?0:$start+1);
    $body=$start===false?'':substr($user,$start,$end===false?null:$end-$start);
    expect_true($body!=='',"{$method} source missing.");
    expect_true(!str_contains(strtolower($body),'u.phone')&&!preg_match('/select[^;]*\bphone\b/is',$body),"{$method} must not expose phone.");
}
$account=file_get_contents($root.'/app/Views/account/index.php')?:'';
$admin=file_get_contents($root.'/app/Views/admin/user-detail.php')?:'';
expect_true(str_contains($account,'phoneDisplay'),'Account owner must be able to see own normalized phone.');
expect_true(str_contains($admin,'phoneDisplay'),'Admin user detail must show phone to admin.');
echo "PASS v10_phone_privacy_contract_test\n";
