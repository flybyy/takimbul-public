<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$bootstrap=file_get_contents(__DIR__.'/../app/Core/Bootstrap.php') ?: '';
$htaccess=file_get_contents(__DIR__.'/../public/.htaccess') ?: '';
foreach(['pagead2.googlesyndication.com','googleads.g.doubleclick.net','tpc.googlesyndication.com'] as $host){
    expect_true(str_contains($bootstrap,$host),"Runtime CSP must allow {$host} for AdSense-compatible external ads.");
    expect_true(str_contains($htaccess,$host),"Apache CSP must allow {$host} for AdSense-compatible external ads.");
}
expect_true(str_contains($bootstrap,'frame-src'),'Runtime CSP must explicitly define frame-src for external ads.');
expect_true(str_contains($htaccess,'frame-src'),'Apache CSP must explicitly define frame-src for external ads.');
echo "PASS v8_adsense_csp_test\n";
