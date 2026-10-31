<?php

declare(strict_types=1);

if(PHP_SAPI!=='cli'){fwrite(STDERR,"CLI only.\n");exit(2);}
$root=dirname(__DIR__);
spl_autoload_register(function(string $class) use($root): void {$prefix='App\\';if(!str_starts_with($class,$prefix))return;$path=$root.'/app/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';if(is_file($path))require $path;});

$password=(string)(getenv('TAKIMBUL_ADMIN_PASSWORD')?:'');
if(($error=\App\Models\User::validatePassword($password))!==null){fwrite(STDERR,"TAKIMBUL_ADMIN_PASSWORD geçersiz: {$error}\n");exit(1);}
$pdo=\App\Core\Database::connection();
$hash=password_hash($password,PASSWORD_DEFAULT);
$stmt=$pdo->prepare('INSERT INTO admins (username,email,password_hash,display_name,status) VALUES (:username,:email,:hash,:name,"active") ON DUPLICATE KEY UPDATE email=VALUES(email),password_hash=VALUES(password_hash),display_name=VALUES(display_name),status="active",updated_at=NOW()');
$stmt->execute(['username'=>'veribayt','email'=>'admin@takimbul.com.tr','hash'=>$hash,'name'=>'Veribayt Yönetim']);
echo "Ayrı yönetici hesabı hazır: veribayt\n";
