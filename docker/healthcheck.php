<?php

declare(strict_types=1);

$root=dirname(__DIR__);
require $root.'/app/Core/Database.php';

try {
    if (!is_file($root.'/public/index.php')) throw new RuntimeException('front controller missing');
    if (!is_dir($root.'/storage/logs') || !is_writable($root.'/storage/logs')) throw new RuntimeException('storage/logs not writable');
    if (!is_dir($root.'/public/uploads/avatars') || !is_writable($root.'/public/uploads/avatars')) throw new RuntimeException('avatar directory not writable');
    $pdo=App\Core\Database::connection();
    $pdo->query('SELECT 1')->fetchColumn();
    echo "TakımBul Docker healthcheck OK\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR,"TakımBul Docker healthcheck FAIL: {$e->getMessage()}\n");
    exit(1);
}
