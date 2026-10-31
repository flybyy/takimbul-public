<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$hits=[];
$files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app'));
foreach($files as $file){
    if(!$file->isFile() || $file->getExtension()!=='php') continue;
    $src=file_get_contents($file->getPathname())?:'';
    preg_match_all("/'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'/s",$src,$matches,PREG_OFFSET_CAPTURE);
    foreach($matches[1]??[] as [$literal,$offset]){
        preg_match_all('/:([A-Za-z_][A-Za-z0-9_]*)/',$literal,$pm);
        $counts=array_count_values($pm[1]??[]);
        foreach($counts as $name=>$count){
            if($count>1)$hits[]=str_replace($root.'/','',$file->getPathname()).':'.$name;
        }
    }
}
expect_same([],array_values(array_unique($hits)),'Repeated named PDO placeholders are unsafe with ATTR_EMULATE_PREPARES=false.');
echo "PASS v10_pdo_placeholder_safety_test\n";
