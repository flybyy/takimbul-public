<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$user=file_get_contents($root.'/app/Models/User.php')?:'';
$auth=file_get_contents($root.'/app/Core/Auth.php')?:'';
$adminAuth=file_get_contents($root.'/app/Core/AdminAuth.php')?:'';
$check(str_contains($user,'WHERE status<>"deleted"') || str_contains($user,"WHERE status<>'deleted'"),'Deleted legacy admin tombstones must not count as users.');
$check(str_contains($auth,"unset(\$_SESSION['admin']"),'User login must terminate any admin session.');
$check(str_contains($adminAuth,"unset(\$_SESSION['user']"),'Admin login must terminate any user session.');
$check(str_contains($auth,"!=='player'") || str_contains($auth,"!== 'player'"),'Normal user session must reject legacy admin roles.');
if($fail){fwrite(STDERR,"FAIL v91_admin_user_boundary_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v91_admin_user_boundary_test\n";
