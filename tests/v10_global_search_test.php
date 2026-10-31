<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\GlobalSearch;
$root=dirname(__DIR__);
expect_true(class_exists(GlobalSearch::class),'GlobalSearch model missing.');
expect_true(method_exists(GlobalSearch::class,'query'),'GlobalSearch::query missing.');
$index=file_get_contents($root.'/public/index.php')?:'';
expect_true(str_contains($index,"'/ara'"),'Global search route missing.');
expect_true(is_file($root.'/app/Views/search/index.php'),'Search view missing.');
$header=file_get_contents($root.'/app/Views/layouts/header.php')?:'';
expect_true(str_contains($header,'action="/ara"'),'Header search form missing.');
echo "PASS v10_global_search_test\n";
