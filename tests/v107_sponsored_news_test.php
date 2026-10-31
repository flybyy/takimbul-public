<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\News;
expect_true(News::isSafeExternalUrl('https://example.com/x'),'https sponsor URL kabul edilmeli.');
expect_true(News::isSafeExternalUrl('http://example.com'),'http sponsor URL kabul edilmeli.');
foreach(['javascript:alert(1)','//evil.example/x','data:text/html,x','mailto:test@example.com'] as $bad) expect_true(!News::isSafeExternalUrl($bad),'Güvensiz sponsor URL reddedilmeli: '.$bad);
$errors=News::validateInput(['title'=>'Sponsorlu haber başlığı','body'=>str_repeat('x',30),'category'=>'TakımBul','status'=>'draft','sponsor_cta_text'=>'İncele','sponsor_cta_url'=>'javascript:alert(1)']);
expect_true(isset($errors['sponsor_cta_url']),'Unsafe CTA validation hatası üretmeli.');
$model=dirname(__DIR__).'/app/Models/NewsSponsor.php';$controller=dirname(__DIR__).'/app/Controllers/NewsSponsorController.php';
expect_true(is_file($model),'NewsSponsor model bulunmalı.');expect_true(is_file($controller),'NewsSponsorController bulunmalı.');
$src=(string)file_get_contents($controller);expect_true(str_contains($src,"requirePermission('sponsors.manage')"),'Sponsor CRUD authorized/super permission ile korunmalı.');
$form=(string)file_get_contents(dirname(__DIR__).'/app/Views/admin/news-form.php');expect_true(str_contains($form,'sponsor_id'),'Haber formunda sponsor seçimi olmalı.');
$index=(string)file_get_contents(dirname(__DIR__).'/app/Views/news/index.php');expect_true(str_contains($index,'Sponsorlu'),'Haber kartında Sponsorlu etiketi olmalı.');
$show=(string)file_get_contents(dirname(__DIR__).'/app/Views/news/show.php');expect_true(str_contains($show,'SPONSORLU İÇERİK'),'Haber detayında sponsor disclosure olmalı.');expect_true(str_contains($show,'sponsored noopener noreferrer'),'Dış sponsor link rel=sponsored olmalı.');
$routes=(string)file_get_contents(dirname(__DIR__).'/public/index.php');expect_true(str_contains($routes,"/admin/haber-sponsorlari"),'Sponsor admin route bulunmalı.');
echo "PASS v107_sponsored_news_test\n";
