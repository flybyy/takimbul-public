<?php
declare(strict_types=1);
function news_must(bool $ok,string $message): void { if(!$ok){fwrite(STDERR,"FAIL v104_news_contract_test\n- {$message}\n");exit(1);} }
$root=dirname(__DIR__);
$index=(string)file_get_contents($root.'/public/index.php');
$migration=$root.'/database/migrations/2026-09-05-v104-news.sql';
foreach(['app/Models/News.php','app/Controllers/NewsController.php','app/Views/news/index.php','app/Views/news/show.php','app/Views/admin/news.php','app/Views/admin/news-form.php'] as $file){news_must(is_file($root.'/'.$file),'News module file missing: '.$file);}
news_must(is_file($migration),'V10.4 news migration missing.');
$sql=(string)file_get_contents($migration);
foreach(['CREATE TABLE IF NOT EXISTS news','slug','status','published_at','view_count','is_featured'] as $needle){news_must(str_contains($sql,$needle),'News schema missing: '.$needle);}
foreach(["'/haberler'","'/haber/{slug}'","'/admin/haberler'","'/admin/haberler/yeni'"] as $route){news_must(str_contains($index,$route),'News route missing: '.$route);}
$header=(string)file_get_contents($root.'/app/Views/layouts/header.php');
$adminHeader=(string)file_get_contents($root.'/app/Views/layouts/admin-header.php');
news_must(str_contains($header,'href="/haberler"'),'Public news navigation missing.');
news_must(str_contains($adminHeader,'href="/admin/haberler"'),'Admin news navigation missing.');
echo "PASS v104_news_contract_test\n";
