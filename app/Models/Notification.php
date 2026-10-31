<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Notification
{
    public static function safeLink(?string $link): ?string
    {
        if ($link === null || $link === '') {
            return null;
        }
        if (!str_starts_with($link, '/') || str_starts_with($link, '//') || str_contains($link, "\n") || str_contains($link, "\r")) {
            return null;
        }
        return strlen($link) <= 255 ? $link : null;
    }

    public static function create(int $userId, string $type, string $title, ?string $body = null, ?string $link = null): void
    {
        if ($userId <= 0) {
            return;
        }
        $statement = Database::connection()->prepare(
            'INSERT INTO notifications (user_id, type, title, body, link) VALUES (:user_id,:type,:title,:body,:link)'
        );
        $statement->execute([
            'user_id'=>$userId,
            'type'=>substr(trim($type),0,80),
            'title'=>substr(trim($title),0,180),
            'body'=>$body !== null ? substr(trim($body),0,500) : null,
            'link'=>self::safeLink($link),
        ]);
    }

    public static function unreadCount(int $userId): int
    {
        $statement = Database::connection()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=:user_id AND read_at IS NULL');
        $statement->execute(['user_id'=>$userId]);
        return (int) $statement->fetchColumn();
    }

    public static function recent(int $userId, int $limit = 80): array
    {
        $sql='SELECT id,type,title,body,link,read_at,created_at FROM notifications WHERE user_id=:user_id ORDER BY created_at DESC LIMIT '.max(1,min(200,$limit));
        $statement=Database::connection()->prepare($sql);
        $statement->execute(['user_id'=>$userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function markRead(int $notificationId, int $userId): ?string
    {
        $statement=Database::connection()->prepare('SELECT link FROM notifications WHERE id=:id AND user_id=:user_id LIMIT 1');
        $statement->execute(['id'=>$notificationId,'user_id'=>$userId]);
        $link=$statement->fetchColumn();
        if ($link === false) {
            return null;
        }
        $update=Database::connection()->prepare('UPDATE notifications SET read_at=COALESCE(read_at,NOW()) WHERE id=:id AND user_id=:user_id');
        $update->execute(['id'=>$notificationId,'user_id'=>$userId]);
        return self::safeLink(is_string($link)?$link:null);
    }

    public static function markAllRead(int $userId): void
    {
        $statement=Database::connection()->prepare('UPDATE notifications SET read_at=COALESCE(read_at,NOW()) WHERE user_id=:user_id AND read_at IS NULL');
        $statement->execute(['user_id'=>$userId]);
    }
}
