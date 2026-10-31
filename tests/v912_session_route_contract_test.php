<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';

$bootstrap=file_get_contents(dirname(__DIR__).'/app/Core/Bootstrap.php')?:'';
expect_true(str_contains($bootstrap,"'/kariyer/vitrin'"),'Video vitrini GET isteğinde session önceden başlamalı.');
expect_true(str_contains($bootstrap,"'/rakip-bul/anlik'"),'Anlık rakip GET isteğinde session önceden başlamalı.');

echo "PASS v912_session_route_contract_test\n";
