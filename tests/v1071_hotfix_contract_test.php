<?php
require __DIR__.'/bootstrap.php';
use App\Core\Icon;
use App\Support\VerificationBadge;

$icon=Icon::svg('arrow');
expect_true(str_contains($icon,'width="20"') && str_contains($icon,'height="20"'),'Inline TakımBul icons must carry intrinsic dimensions, not rely only on CSS.');
$badge=VerificationBadge::render(true);
expect_true(str_contains($badge,'width="17"') && str_contains($badge,'height="17"'),'Verification badge SVG must carry intrinsic dimensions.');

$header=(string)file_get_contents(__DIR__.'/../app/Views/layouts/header.php');
$adminHeader=(string)file_get_contents(__DIR__.'/../app/Views/layouts/admin-header.php');
expect_true(!str_contains($header,'<details class="tb-nav-group'),'Desktop navigation must not rely on native details markers.');
expect_true(str_contains($header,'/assets/css/app-v108.css'),'Public header must use the current cache-busting stylesheet.');
expect_true(str_contains($adminHeader,'/assets/css/app-v108.css'),'Admin header must use the current cache-busting stylesheet.');
expect_true(is_file(__DIR__.'/../public/assets/css/app-v108.css'),'Current stylesheet must exist.');

$css=(string)@file_get_contents(__DIR__.'/../public/assets/css/app-v108.css');
expect_true(str_contains($css,'TAKIMBUL V10.7.1 HOTFIX'),'V10.7.1 hotfix layer must be preserved inside the current stylesheet.');
foreach(['admin-page-head','admin-kpi-grid','admin-filter-bar','admin-table-wrap','admin-detail-grid','admin-detail-list','admin-staff-form'] as $class){
    expect_true((bool)preg_match('/\.' . preg_quote($class,'/') . '(?=[,{\s>.:#])/', $css),'Missing admin hotfix style for .'.$class);
}
echo "PASS v1071_hotfix_contract_test\n";
