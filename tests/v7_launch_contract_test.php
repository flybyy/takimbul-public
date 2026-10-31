<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$index=file_get_contents(__DIR__.'/../public/index.php') ?: '';
$header=file_get_contents(__DIR__.'/../app/Views/layouts/header.php') ?: '';
$htaccess=file_get_contents(__DIR__.'/../public/.htaccess') ?: '';
$schema=file_get_contents(__DIR__.'/../database/schema.sql') ?: '';
foreach(['/robots.txt','/sitemap.xml','/sikayet','/admin/sikayetler','/admin/scout-basvurulari'] as $route){
    expect_true(str_contains($index,$route),"Launch route {$route} must exist.");
}
foreach(['rel="canonical"','og:title','twitter:card','application/ld+json','name="robots"'] as $needle){
    expect_true(str_contains($header,$needle),"Head must contain {$needle}.");
}
foreach(['Content-Security-Policy','Strict-Transport-Security','Cache-Control'] as $needle){
    expect_true(str_contains($htaccess,$needle),"Apache config must contain {$needle}.");
}
expect_true(str_contains($schema,'auth_rate_limits'), 'Schema must include auth rate limiting table.');
expect_true(str_contains($schema,'user_tokens') && str_contains($schema,'email_verified_at'), 'Schema must include verification/reset infrastructure.');
expect_true(str_contains($schema,'reporter_user_id'), 'Reports schema must remain available.');
echo "PASS v7_launch_contract_test\n";
