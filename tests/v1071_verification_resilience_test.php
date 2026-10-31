<?php
require __DIR__.'/bootstrap.php';
use App\Models\AdminAuditLog;
use App\Models\User;

// Test environment intentionally has no pdo_mysql. These operations must fail closed,
// return false and never bubble a generic 500-worthy exception.
expect_same(false,AdminAuditLog::record(1,'test.action','user',1,['verified'=>true]),'Audit logging must be non-fatal when storage is unavailable.');
expect_same(false,User::setProfileVerified(1,true,1),'Profile verification storage failure must be reported as false, not thrown.');

$controller=(string)file_get_contents(__DIR__.'/../app/Controllers/AdminController.php');
expect_true(str_contains($controller,"if(!User::setProfileVerified"),'Verification controller must handle a failed verification write explicitly.');
echo "PASS v1071_verification_resilience_test\n";
