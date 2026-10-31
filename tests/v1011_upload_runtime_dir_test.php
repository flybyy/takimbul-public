<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
expect_true(is_dir($root.'/public/uploads/avatars'),'Final package must preserve the avatar upload directory before launch-check runs.');
expect_true(is_file($root.'/public/uploads/avatars/.gitkeep'),'Avatar upload directory must survive ZIP extraction.');
echo "PASS v1011_upload_runtime_dir_test\n";
