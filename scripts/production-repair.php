<?php

declare(strict_types=1);

if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__);
spl_autoload_register(function(string $class) use($root): void {$prefix='App\\';if(!str_starts_with($class,$prefix))return;$path=$root.'/app/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';if(is_file($path))require $path;});

use App\Core\Database;
use App\Core\SchemaRepair;

$apply=in_array('--apply',$argv,true);
try{$pdo=Database::connection();}catch(Throwable $e){fwrite(STDERR,"[!!] DB bağlantısı kurulamadı: {$e->getMessage()}\n");exit(2);}

$printAudit=static function(array $a): void {
    printf("%s DB runtime şeması\n",($a['ok']??false)?'[OK]':'[!!]');
    foreach(['missing_tables'=>'Eksik tablo','missing_columns'=>'Eksik kolon','missing_indexes'=>'Eksik index','enum_mismatches'=>'ENUM uyumsuzluğu'] as $key=>$label){$items=$a[$key]??[];if($items!==[])echo "  - {$label}: ".implode(', ',$items)."\n";}
    echo '  - Ayrı admin: '.(($a['admin_ready']??false)?'OK':'EKSİK')."\n";
    echo '  - E-posta doğrulama: '.(($a['email_verification_off']??false)?'KAPALI':'KONTROL GEREKİYOR')."\n";
};

$before=SchemaRepair::audit($pdo);$printAudit($before);
if(!$apply){
    if(!($before['ok']??false))echo "\nDeğişiklik yapılmadı. Güvenli onarım için:\nphp scripts/production-repair.php --apply\n";
    else echo "\nŞema onarıma ihtiyaç duymuyor.\n";
    exit(($before['ok']??false)?0:1);
}

echo "\n--- SAFE APPLY ---\n";
$result=SchemaRepair::apply($pdo);
foreach($result['actions']??[] as $action)echo "[OK] {$action}\n";
foreach($result['errors']??[] as $error)echo "[!!] {$error}\n";
echo "\n--- AFTER ---\n";$printAudit($result['after']??[]);
if(!($result['ok']??false)){fwrite(STDERR,"\nOnarım tamamlanamadı. Gerçek kullanıcı alımını açma.\n");exit(1);}
echo "\nOnarım tamamlandı. Şimdi: php scripts/production-smoke.php\n";exit(0);
