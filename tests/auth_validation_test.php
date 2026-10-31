<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Models\PlayerProfile;
use App\Models\User;

$errors = User::validateRegistration([
    'display_name' => 'Murat',
    'email' => 'not-an-email',
    'password' => '123',
    'province' => 'Antalya',
    'district' => 'Alanya',
]);
expect_true(isset($errors['email']), 'Invalid e-mail should be rejected');
expect_true(isset($errors['password']), 'Password shorter than 8 characters should be rejected');

$profileErrors = PlayerProfile::validateInput([
    'primary_position' => 'Başkan',
    'secondary_position' => '',
    'dominant_foot' => 'Orta',
    'birth_year' => '2005',
    'height_cm' => '180',
    'visibility' => 'public',
]);
expect_true(isset($profileErrors['primary_position']), 'Unknown football position should be rejected');
expect_true(isset($profileErrors['dominant_foot']), 'Unknown dominant foot should be rejected');

$valid = PlayerProfile::validateInput([
    'primary_position' => 'Forvet',
    'secondary_position' => 'Kanat',
    'dominant_foot' => 'Sağ',
    'birth_year' => '2004',
    'height_cm' => '181',
    'visibility' => 'members',
]);
expect_same([], $valid, 'Valid player profile should pass validation');

echo "PASS auth_validation_test\n";
