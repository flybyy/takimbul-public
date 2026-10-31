<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$index=file_get_contents(__DIR__.'/../public/index.php') ?: '';
$controllerPath=__DIR__.'/../app/Controllers/AdController.php';
$adminIndex=file_get_contents(__DIR__.'/../app/Views/admin/index.php') ?: '';
foreach(['/admin/reklamlar','/admin/reklamlar/yeni','/admin/reklamlar/{id}/duzenle','/admin/reklamlar/{id}/durum','/admin/reklamlar/{id}/sil','/reklam/tikla/{id}'] as $route){
    expect_true(str_contains($index,$route),"Router must expose {$route}.");
}
expect_true(is_file($controllerPath),'AdController must exist.');
$controller=is_file($controllerPath)?(file_get_contents($controllerPath) ?: ''):'';
expect_true(str_contains($controller,"AdminAuth::requirePermission('ads.manage')"),'AdController admin methods must use isolated admin gate with ads.manage permission.');
expect_true(str_contains($controller,'Csrf::validate'),'Ad mutations must validate CSRF.');
expect_true(str_contains($adminIndex,'Reklam Merkezi'),'Admin dashboard must link to Reklam Merkezi.');
expect_true(is_file(__DIR__.'/../app/Views/admin/ads/index.php'),'Admin ad list view must exist.');
expect_true(is_file(__DIR__.'/../app/Views/admin/ads/form.php'),'Admin ad form view must exist.');
echo "PASS ad_admin_contract_test\n";
