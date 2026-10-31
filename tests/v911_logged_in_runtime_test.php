<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';

$_SERVER['REQUEST_URI']='/';
$_SERVER['REQUEST_METHOD']='GET';
if(session_status()!==PHP_SESSION_ACTIVE){session_start();}
$_SESSION['user']=[
    'id'=>42,
    'name'=>'Test Oyuncu',
    'email'=>'test@example.com',
    'role'=>'player',
];
$_SESSION['_auth_checked_at']=time();
$title='Test';

ob_start();
require dirname(__DIR__).'/app/Views/layouts/header.php';
$html=(string)ob_get_clean();

expect_true(str_contains($html,'Test Oyuncu'),'Giriş yapmış kullanıcı header içinde görünmeli.');
expect_true(!str_contains($html,'Yönetim'),'Normal kullanıcı headerında yönetim bağlantısı olmamalı.');

$career=file_get_contents(dirname(__DIR__).'/app/Controllers/CareerController.php')?:'';
expect_true(!str_contains($career,'Auth::isAdmin'),'Kariyer görünürlüğü kaldırılmış Auth::isAdmin metodunu çağırmamalı.');
expect_true(str_contains($career,'AdminAuth::id'),'Kariyer özel profil erişiminde ayrı admin oturumu kullanılmalı.');

echo "PASS v911_logged_in_runtime_test\n";
