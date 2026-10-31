<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Models\ScoutAccount;

expect_true(!ScoutAccount::canDiscoverFromStatus(null), 'No scout account cannot discover');
expect_true(!ScoutAccount::canDiscoverFromStatus('pending'), 'Pending scout cannot discover');
expect_true(!ScoutAccount::canDiscoverFromStatus('rejected'), 'Rejected scout cannot discover');
expect_true(ScoutAccount::canDiscoverFromStatus('approved'), 'Approved scout can discover');

$errors=ScoutAccount::validateApplication(['account_type'=>'agent','organization_name'=>'A']);
expect_true(isset($errors['account_type']), 'Unknown scout type should be rejected');
expect_true(isset($errors['organization_name']), 'Organization name should have a useful length');
expect_same([], ScoutAccount::validateApplication(['account_type'=>'club','organization_name'=>'Alanya Futbol Kulübü','website'=>'https://example.com']), 'Valid club application should pass');

echo "PASS scout_rules_test\n";
