<?php

declare(strict_types=1);
require __DIR__.'/bootstrap.php';

$root=dirname(__DIR__);
$index=file_get_contents($root.'/public/index.php')?:'';

preg_match_all('/\$(\w+)\s*=\s*new\s+(\w+)\(\)\s*;/',$index,$inst,PREG_SET_ORDER);
$vars=[];
foreach($inst as $row) $vars[$row[1]]=$row[2];

preg_match_all('/\$router->(get|post)\(\s*[\'\"]([^\'\"]+)[\'\"]\s*,\s*fn(?:\([^)]*\))?\s*=>\s*\$(\w+)->(\w+)\(/i',$index,$matches,PREG_SET_ORDER);
expect_true(count($matches)>=100,'Route bütünlük testi güncel route listesini okuyabilmeli.');
foreach($matches as $route){
    $verb=strtoupper($route[1]);$path=$route[2];$var=$route[3];$method=$route[4];
    expect_true(isset($vars[$var]),"Route controller değişkeni bulunamadı: {$verb} {$path} -> $".$var);
    $class='App\\Controllers\\'.$vars[$var];
    expect_true(class_exists($class),"Route controller sınıfı yok: {$class}");
    expect_true(method_exists($class,$method),"Route metodu yok: {$verb} {$path} -> {$class}::{$method}");
    $ref=new ReflectionMethod($class,$method);
    expect_true($ref->isPublic(),"Route metodu public değil: {$class}::{$method}");

    if($verb==='POST'){
        $file=(string)$ref->getFileName();$lines=file($file)?:[];
        $body=implode('',array_slice($lines,max(0,$ref->getStartLine()-1),$ref->getEndLine()-$ref->getStartLine()+1));
        expect_true(str_contains($body,'Csrf::validate'),"POST route CSRF kontrolü eksik: {$path}");
    }
}

foreach(glob($root.'/app/Controllers/*.php')?:[] as $file){
    $text=file_get_contents($file)?:'';
    preg_match_all('/View::render\(\s*[\'\"]([^\'\"]+)[\'\"]/',$text,$views);
    foreach($views[1]??[] as $view){
        expect_true(is_file($root.'/app/Views/'.$view.'.php'),"View dosyası eksik: {$view}");
    }
}

echo "PASS v912_route_integrity_test\n";
