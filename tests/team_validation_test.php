<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Models\Team;

$errors = Team::validateInput([
    'name' => 'A',
    'province' => '',
    'district' => '',
    'founded_year' => '1800',
]);
expect_true(isset($errors['name']), 'One-character team name should be rejected');
expect_true(isset($errors['province']), 'Province is required');
expect_true(isset($errors['district']), 'District is required');
expect_true(isset($errors['founded_year']), 'Unreasonable founded year should be rejected');

$valid = Team::validateInput([
    'name' => 'Alanya Kartalları',
    'province' => 'Antalya',
    'district' => 'Alanya',
    'founded_year' => '2026',
    'description' => 'Haftada iki maç yapan rekabetçi ekip.',
]);
expect_same([], $valid, 'Valid team input should pass');
expect_same('alanya-kartallari', Team::slugify('Alanya Kartalları'), 'Turkish characters should normalize in slugs');

echo "PASS team_validation_test\n";
