<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$logger=(string)@file_get_contents($root.'/app/Core/Logger.php');
$bootstrap=(string)@file_get_contents($root.'/app/Core/Bootstrap.php');
$error=(string)@file_get_contents($root.'/app/Views/errors/500.php');
expect_true(str_contains($logger,'error_id'),'Production logs must include a correlation error_id.');
expect_true(str_contains($logger,'request_path'),'Production logs must include request_path.');
expect_true(str_contains($bootstrap,'$errorId'),'Global exception handler must capture the support error id.');
expect_true(str_contains($error,'Hata kodu'),'500 UI must show a safe support/error code.');
echo "PASS v1011_error_diagnostics_test\n";
