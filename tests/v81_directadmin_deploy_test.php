<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$ht=$root.'/.htaccess';$check(is_file($ht),'DirectAdmin public_html uyumluluğu için kök .htaccess eksik');
$text=is_file($ht)?(file_get_contents($ht)?:''):'';
foreach(['app|config|database|docs|scripts|storage|tests','[F,L,NC]','public/$1/$2','public/index.php'] as $n)$check(str_contains($text,$n),'Kök .htaccess eksik sözleşme: '.$n);
$readme=file_get_contents($root.'/README.md')?:'';$check(str_contains($readme,'DirectAdmin tek klasör kurulumu'),'README DirectAdmin tek klasör kurulumunu anlatmıyor');
if($fail){fwrite(STDERR,"FAIL v81_directadmin_deploy_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v81_directadmin_deploy_test\n";
