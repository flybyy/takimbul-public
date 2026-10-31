<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\AdminAuditLog;
$raw=['password'=>'secret','password_hash'=>'hash','csrf'=>'token','session_id'=>'sid','email'=>'private@example.com','phone'=>'05550000000','safe'=>'ok','nested'=>['token'=>'x','state'=>'yes']];
$san=AdminAuditLog::sanitizeMetadata($raw);
foreach(['password','password_hash','csrf','session_id','email','phone'] as $key) expect_true(!array_key_exists($key,$san),'Audit metadata '.$key.' alanını saklamamalı.');
expect_same('ok',$san['safe']??null,'Güvenli metadata korunmalı.');
expect_true(!isset($san['nested']['token']),'Nested secret temizlenmeli.');
expect_same('yes',$san['nested']['state']??null,'Nested güvenli değer korunmalı.');
$src=(string)file_get_contents(dirname(__DIR__).'/app/Models/AdminAuditLog.php');
expect_true(str_contains($src,'INSERT INTO admin_audit_log'),'Audit model append insert kullanmalı.');
expect_true(!preg_match('/\b(UPDATE|DELETE)\s+admin_audit_log\b/i',$src),'Audit log append-only olmalı.');
echo "PASS v107_audit_log_test\n";
