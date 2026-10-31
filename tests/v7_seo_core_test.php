<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Core\Seo;
expect_same('https://takimbul.com.tr/oyuncu-bul', Seo::canonical('/oyuncu-bul?province=Antalya&utm_source=x'), 'Canonical must drop query parameters.');
expect_same('index,follow', Seo::robotsFor('/takim/42', []), 'Public team profile must be indexable.');
expect_same('noindex,follow', Seo::robotsFor('/oyuncu-bul', ['province'=>'Antalya']), 'Filtered result URLs must be noindex.');
expect_same('noindex,nofollow', Seo::robotsFor('/mesajlar', []), 'Private messages must never be indexed.');
expect_same('noindex,nofollow', Seo::robotsFor('/mac/42', []), 'Private match rooms must never be indexed.');
expect_same('index,follow', Seo::robotsFor('/', ['utm_source'=>'instagram']), 'Tracking parameters alone must not force noindex.');
$home=Seo::forRequest('/', [], ['title'=>'Türkiye’nin Futbol Ağı']);
expect_true(str_contains($home['title'],'Halı Saha'), 'Home SEO title should target halı saha discovery intent.');
expect_true(isset($home['jsonLd']) && count($home['jsonLd'])>=2, 'Home must expose WebSite and Organization JSON-LD.');
echo "PASS v7_seo_core_test\n";
