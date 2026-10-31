<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Core\Seo;
foreach(['/hesabim','/takim-bul','/takim-ariyorum','/ara'] as $path){
    expect_same('noindex,nofollow',Seo::robotsFor($path,[]),"{$path} must not be indexed.");
}
expect_same('noindex,nofollow',Seo::robotsFor('/ara',['q'=>'kaleci']),'Search result pages must not be indexed.');
expect_same('index,follow',Seo::robotsFor('/oyuncu-bul',[]),'Public discovery page must remain indexable.');
echo "PASS v10_seo_private_routes_test\n";
