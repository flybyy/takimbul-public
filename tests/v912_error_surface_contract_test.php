<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';

$root=dirname(__DIR__);
foreach([403,404,419,500] as $status){
    expect_true(is_file($root."/app/Views/errors/{$status}.php"),"{$status} hata ekranı eksik.");
}
expect_true(class_exists('App\\Core\\HttpError'),'Ortak HTTP hata yanıtlayıcısı eksik.');

$controllerText='';
foreach(glob($root.'/app/Controllers/*.php')?:[] as $file){
    if(basename($file)==='SeoController.php') continue;
    $controllerText.=file_get_contents($file)?:'';
}
expect_true(!preg_match('/http_response_code\((403|404|419)\)\s*;\s*echo\s+[\'\"]/',$controllerText),
    '403/404/419 kullanıcı hataları düz metin echo ile dönmemeli.');
expect_true(!preg_match('/http_response_code\(419\)\s*;\s*return\s*;/',$controllerText),
    'CSRF 419 yanıtları boş sayfa döndürmemeli.');

$router=file_get_contents($root.'/app/Core/Router.php')?:'';
expect_true(str_contains($router,'HttpError::render(404'),'Router fallback profesyonel 404 ekranını kullanmalı.');

echo "PASS v912_error_surface_contract_test\n";
