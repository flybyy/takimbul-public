<?php

declare(strict_types=1);

$root=dirname(__DIR__);
spl_autoload_register(function(string $class) use ($root): void {
    $prefix='App\\';if(!str_starts_with($class,$prefix))return;$relative=substr($class,strlen($prefix));$file=$root.'/app/'.str_replace('\\','/',$relative).'.php';if(is_file($file))require$file;
});
require $root.'/app/Core/Bootstrap.php';
\App\Core\Bootstrap::init();
$count=\App\Models\AmateurPlayerAd::expireStale();
printf("Amatör futbolcu pazarı bakım tamamlandı. Süresi dolan: %d\n",$count);
