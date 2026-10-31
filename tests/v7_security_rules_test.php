<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Core\RateLimiter;
use App\Models\Report;
$_SESSION=[];
expect_true(RateLimiter::allowInSession('login:test',2,60,1000), 'First login attempt must be allowed.');
expect_true(RateLimiter::allowInSession('login:test',2,60,1001), 'Second login attempt must be allowed.');
expect_true(!RateLimiter::allowInSession('login:test',2,60,1002), 'Third login attempt in the window must be blocked.');
expect_true(RateLimiter::allowInSession('login:test',2,60,1062), 'Limiter must reopen after window expires.');
$errors=Report::validate(['target_type'=>'user','target_id'=>3,'reason'=>'fake_profile','details'=>'']);
expect_same([], $errors, 'A valid report payload should pass validation.');
$errors=Report::validate(['target_type'=>'evil','target_id'=>0,'reason'=>'x','details'=>str_repeat('a',701)]);
expect_true(isset($errors['target_type']) && isset($errors['target_id']) && isset($errors['reason']) && isset($errors['details']), 'Invalid report payload must be rejected.');
echo "PASS v7_security_rules_test\n";
