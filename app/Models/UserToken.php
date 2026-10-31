<?php

declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
use PDO;
final class UserToken
{
    public static function issue(int $userId,string $type,int $ttlSeconds): string
    {
        $token=bin2hex(random_bytes(32));$hash=hash('sha256',$token);$expires=date('Y-m-d H:i:s',time()+$ttlSeconds);
        $pdo=Database::connection();
        $del=$pdo->prepare('DELETE FROM user_tokens WHERE user_id=:user_id AND type=:type AND used_at IS NULL');$del->execute(['user_id'=>$userId,'type'=>$type]);
        $stmt=$pdo->prepare('INSERT INTO user_tokens (user_id,type,token_hash,expires_at) VALUES (:user_id,:type,:token_hash,:expires_at)');
        $stmt->execute(['user_id'=>$userId,'type'=>$type,'token_hash'=>$hash,'expires_at'=>$expires]); return $token;
    }
    public static function findValid(string $token,string $type): ?array
    {
        if(!preg_match('/^[a-f0-9]{64}$/',$token)) return null;
        $stmt=Database::connection()->prepare('SELECT ut.id,ut.user_id,u.email,u.display_name FROM user_tokens ut INNER JOIN users u ON u.id=ut.user_id WHERE ut.token_hash=:hash AND ut.type=:type AND ut.used_at IS NULL AND ut.expires_at>NOW() LIMIT 1');
        $stmt->execute(['hash'=>hash('sha256',$token),'type'=>$type]);$row=$stmt->fetch(PDO::FETCH_ASSOC);return is_array($row)?$row:null;
    }
    public static function consume(int $id): void { $stmt=Database::connection()->prepare('UPDATE user_tokens SET used_at=NOW() WHERE id=:id AND used_at IS NULL');$stmt->execute(['id'=>$id]); }
}
