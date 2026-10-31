<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class RateLimiter
{
    public static function allow(string $key, int $limit, int $windowSeconds): bool
    {
        try {
            $pdo=Database::connection();
            $hash=hash('sha256',$key); $now=time();
            $stmt=$pdo->prepare('SELECT hits,window_started_at FROM auth_rate_limits WHERE key_hash=:key_hash LIMIT 1');
            $stmt->execute(['key_hash'=>$hash]); $row=$stmt->fetch();
            if (!$row) {
                $ins=$pdo->prepare('INSERT INTO auth_rate_limits (key_hash,hits,window_started_at,updated_at) VALUES (:key_hash,1,NOW(),NOW())');
                $ins->execute(['key_hash'=>$hash]); return true;
            }
            $start=strtotime((string)$row['window_started_at']) ?: $now;
            if (($now-$start) >= $windowSeconds) {
                $reset=$pdo->prepare('UPDATE auth_rate_limits SET hits=1,window_started_at=NOW(),updated_at=NOW() WHERE key_hash=:key_hash');
                $reset->execute(['key_hash'=>$hash]); return true;
            }
            if ((int)$row['hits'] >= $limit) return false;
            $upd=$pdo->prepare('UPDATE auth_rate_limits SET hits=hits+1,updated_at=NOW() WHERE key_hash=:key_hash');
            $upd->execute(['key_hash'=>$hash]); return true;
        } catch (Throwable) {
            return self::allowInSession($key,$limit,$windowSeconds,time());
        }
    }

    public static function allowInSession(string $key, int $limit, int $windowSeconds, int $now): bool
    {
        $_SESSION['_rate_limits'] ??= [];
        $entry=$_SESSION['_rate_limits'][$key] ?? ['hits'=>0,'start'=>$now];
        if (($now-(int)$entry['start']) >= $windowSeconds) $entry=['hits'=>0,'start'=>$now];
        if ((int)$entry['hits'] >= $limit) { $_SESSION['_rate_limits'][$key]=$entry; return false; }
        $entry['hits']=(int)$entry['hits']+1; $_SESSION['_rate_limits'][$key]=$entry; return true;
    }

    public static function clientKey(string $scope, string $identity = ''): string
    {
        $ip=(string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        return $scope . '|' . $ip . '|' . strtolower(trim($identity));
    }
}
