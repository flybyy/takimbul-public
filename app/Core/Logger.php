<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $payload = ['time'=>date('c'),'message'=>$message,'context'=>$context];
        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND|LOCK_EX);
    }

    public static function exception(Throwable $e): string
    {
        try{$errorId='TB-'.strtoupper(bin2hex(random_bytes(6)));}catch(Throwable){$errorId='TB-'.strtoupper(substr(hash('sha256',microtime(true).$e->getMessage()),0,12));}
        self::error($e->getMessage(), [
            'error_id'=>$errorId,
            'class'=>$e::class,
            'file'=>$e->getFile(),
            'line'=>$e->getLine(),
            'request_method'=>$_SERVER['REQUEST_METHOD']??(PHP_SAPI==='cli'?'CLI':null),
            'request_path'=>parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH)?:null,
            'user_id'=>isset($_SESSION['user']['id'])?(int)$_SESSION['user']['id']:null,
            'admin_id'=>isset($_SESSION['admin']['id'])?(int)$_SESSION['admin']['id']:null,
            'trace'=>AppConfig::debug()?$e->getTraceAsString():null,
        ]);
        return $errorId;
    }
}
