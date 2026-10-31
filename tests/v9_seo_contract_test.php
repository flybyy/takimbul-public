<?php
declare(strict_types=1);require __DIR__.'/bootstrap.php';
use App\Core\Seo;
expect_same('index,follow',Seo::robotsFor('/musait-oyuncular',[]),'public available-player pool should be indexable');
expect_same('noindex,nofollow',Seo::robotsFor('/musaitim',[]),'availability editor should be private');
expect_same('noindex,nofollow',Seo::robotsFor('/rakip-bul/anlik',[]),'instant matching requires login and should not index');
expect_same('noindex,nofollow',Seo::robotsFor('/kariyer/vitrin',[]),'showcase editor should not index');
$seo=Seo::forRequest('/musait-oyuncular',[],[]);expect_true(str_contains($seo['title'],'Müsait'),'available players should have dedicated title');
$controller=file_get_contents(dirname(__DIR__).'/app/Controllers/SeoController.php')?:'';expect_true(str_contains($controller,"'/musait-oyuncular'"),'sitemap should include available-player pool');
echo "PASS v9_seo_contract_test\n";
