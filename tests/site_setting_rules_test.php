<?php
declare(strict_types=1);require __DIR__.'/bootstrap.php';
use App\Models\SiteSetting;
expect_true(class_exists(SiteSetting::class),'SiteSetting model must exist');
expect_same(null,SiteSetting::validate('announcement_enabled','1'),'announcement toggle 1 should pass');
expect_true(SiteSetting::validate('announcement_enabled','yes')!==null,'toggle accepts only 0/1');
expect_same(null,SiteSetting::validate('support_email','destek@takimbul.com.tr'),'support email should pass');
expect_true(SiteSetting::validate('support_email','bad')!==null,'invalid email should fail');
expect_true(SiteSetting::validate('unknown_key','x')!==null,'unknown setting should fail');
echo "PASS site_setting_rules_test\n";
