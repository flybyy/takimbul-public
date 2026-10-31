<?php
require __DIR__.'/bootstrap.php';

$targets = [
    __DIR__.'/../app/Views/auth/register.php',
    __DIR__.'/../app/Views/account/index.php',
    __DIR__.'/../app/Models/User.php',
];
$combined='';
foreach($targets as $file){
    $combined .= "\n" . file_get_contents($file);
}

expect_true(!preg_match('/placeholder=\"[^\"]*05\d{2}[^\"]*\"/u',$combined), 'Registration/account UI must not hard-code a real-looking Turkish mobile number as a placeholder.');

$register=file_get_contents(__DIR__.'/../app/Views/auth/register.php');
expect_true(str_contains($register, 'placeholder="futbolcu34"'), 'Username placeholder must be generic.');
expect_true(str_contains($register, 'placeholder="05XX XXX XX XX"'), 'Phone placeholder must be generic and non-personal.');

echo "PASS v106_privacy_hygiene_test\n";
