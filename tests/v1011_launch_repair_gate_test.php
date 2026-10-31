<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';

$root=dirname(__DIR__);
$check=(string)@file_get_contents($root.'/scripts/launch-check.php');
expect_true(str_contains($check,'SchemaRepair'),'launch-check must use the shared schema repair manifest.');
expect_true(str_contains($check,'production-repair.php --apply'),'launch-check must print the safe repair command when schema is incomplete.');
expect_true(str_contains($check,'production-smoke.php'),'launch-check must point to runtime smoke verification.');
expect_true(str_contains($check,"'DB runtime şeması'"),'Runtime schema audit must be a named launch check.');

echo "PASS v1011_launch_repair_gate_test\n";
