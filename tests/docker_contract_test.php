<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$fail=[];
$mustExist=[
    'Dockerfile',
    'docker-compose.yml',
    '.dockerignore',
    '.env.docker.example',
    'docker/apache-vhost.conf',
    'docker/app.docker.php',
    'docker/entrypoint.sh',
    'docker/healthcheck.php',
    'scripts/docker-smoke.sh',
];
foreach($mustExist as $file){if(!is_file($root.'/'.$file))$fail[]='missing '.$file;}

$compose=(string)@file_get_contents($root.'/docker-compose.yml');
foreach(['app:','db:','depends_on:','service_healthy','takimbul_db_data','${APP_PORT:-8080}:80'] as $needle){
    if(!str_contains($compose,$needle))$fail[]='compose '.$needle;
}
$dockerfile=(string)@file_get_contents($root.'/Dockerfile');
foreach(['php:8.2-apache','pdo_mysql','gd','mbstring','a2enmod rewrite headers expires deflate','ENTRYPOINT'] as $needle){
    if(!str_contains($dockerfile,$needle))$fail[]='dockerfile '.$needle;
}
$entrypoint=(string)@file_get_contents($root.'/docker/entrypoint.sh');
foreach(['production-repair.php --apply','TAKIMBUL_ADMIN_PASSWORD','apache2-foreground'] as $needle){
    if(!str_contains($entrypoint,$needle))$fail[]='entrypoint '.$needle;
}
$config=(string)@file_get_contents($root.'/docker/app.docker.php');
foreach(['DB_HOST','DB_DATABASE','DB_USERNAME','DB_PASSWORD','APP_URL','APP_ENV','APP_DEBUG'] as $needle){
    if(!str_contains($config,$needle))$fail[]='docker config '.$needle;
}
$readme=(string)@file_get_contents($root.'/README.md');
foreach(['## Docker ile Çalıştırma','docker compose up -d --build','http://localhost:8080','docker compose down -v'] as $needle){
    if(!str_contains($readme,$needle))$fail[]='README '.$needle;
}

if($fail!==[]){fwrite(STDERR,"FAIL: ".implode(' | ',$fail)."\n");exit(1);}echo "PASS docker_contract_test\n";
