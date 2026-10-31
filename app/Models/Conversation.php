<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

final class Conversation
{
    public static function validateMessage(string $body): array
    {
        $body = trim($body);
        if ($body === '') {
            return ['body' => 'Mesaj boş olamaz.'];
        }
        if (strlen($body) > 2000) {
            return ['body' => 'Mesaj 2000 karakteri geçemez.'];
        }
        return [];
    }

    public static function validateRecipient(int $senderId, int $recipientId): array
    {
        if ($senderId <= 0 || $recipientId <= 0 || $senderId === $recipientId) {
            return ['recipient' => 'Geçerli bir alıcı seç.'];
        }
        return [];
    }

    /** @return array{0:int,1:int} */
    public static function orderedPair(int $userA, int $userB): array
    {
        return $userA < $userB ? [$userA, $userB] : [$userB, $userA];
    }

    public static function send(int $senderId, int $recipientId, string $body): int
    {
        if (self::validateRecipient($senderId, $recipientId) !== []) {
            throw new RuntimeException('Geçersiz mesaj alıcısı.');
        }
        $errors = self::validateMessage($body);
        if ($errors !== []) {
            throw new RuntimeException($errors['body']);
        }
        if (User::findPublic($recipientId) === null) {
            throw new RuntimeException('Mesaj göndermek istediğin kullanıcı bulunamadı.');
        }

        [$low, $high] = self::orderedPair($senderId, $recipientId);
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $insertThread = $pdo->prepare(
                'INSERT INTO direct_threads (user_low_id, user_high_id, updated_at)
                 VALUES (:low, :high, NOW())
                 ON DUPLICATE KEY UPDATE updated_at = updated_at'
            );
            $insertThread->execute(['low' => $low, 'high' => $high]);

            $find = $pdo->prepare('SELECT id FROM direct_threads WHERE user_low_id=:low AND user_high_id=:high LIMIT 1 FOR UPDATE');
            $find->execute(['low' => $low, 'high' => $high]);
            $threadId = (int) $find->fetchColumn();
            if ($threadId <= 0) {
                throw new RuntimeException('Mesaj konuşması oluşturulamadı.');
            }

            $insertMessage = $pdo->prepare(
                'INSERT INTO direct_messages (thread_id, sender_user_id, body) VALUES (:thread_id, :sender_user_id, :body)'
            );
            $insertMessage->execute([
                'thread_id' => $threadId,
                'sender_user_id' => $senderId,
                'body' => trim($body),
            ]);
            $touch = $pdo->prepare('UPDATE direct_threads SET updated_at=NOW() WHERE id=:id');
            $touch->execute(['id' => $threadId]);
            $pdo->commit();
            return $threadId;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function threadsForUser(int $userId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT t.id, t.updated_at,
                    CASE WHEN t.user_low_id=:uid1 THEN t.user_high_id ELSE t.user_low_id END AS other_user_id,
                    u.display_name AS other_name, u.province AS other_province, u.district AS other_district,
                    (SELECT dm.body FROM direct_messages dm WHERE dm.thread_id=t.id ORDER BY dm.id DESC LIMIT 1) AS last_message,
                    (SELECT dm.created_at FROM direct_messages dm WHERE dm.thread_id=t.id ORDER BY dm.id DESC LIMIT 1) AS last_message_at,
                    (SELECT COUNT(*) FROM direct_messages dm WHERE dm.thread_id=t.id AND dm.sender_user_id<>:uid2 AND dm.read_at IS NULL) AS unread_count
             FROM direct_threads t
             INNER JOIN users u ON u.id = CASE WHEN t.user_low_id=:uid3 THEN t.user_high_id ELSE t.user_low_id END
             WHERE t.user_low_id=:uid4 OR t.user_high_id=:uid5
             ORDER BY t.updated_at DESC'
        );
        $statement->execute(['uid1'=>$userId,'uid2'=>$userId,'uid3'=>$userId,'uid4'=>$userId,'uid5'=>$userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findForUser(int $threadId, int $userId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT t.id, t.user_low_id, t.user_high_id,
                    CASE WHEN t.user_low_id=:uid1 THEN t.user_high_id ELSE t.user_low_id END AS other_user_id,
                    u.display_name AS other_name, u.province AS other_province, u.district AS other_district
             FROM direct_threads t
             INNER JOIN users u ON u.id = CASE WHEN t.user_low_id=:uid2 THEN t.user_high_id ELSE t.user_low_id END
             WHERE t.id=:thread_id AND (t.user_low_id=:uid3 OR t.user_high_id=:uid4) LIMIT 1'
        );
        $statement->execute(['uid1'=>$userId,'uid2'=>$userId,'thread_id'=>$threadId,'uid3'=>$userId,'uid4'=>$userId]);
        $thread = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($thread)) {
            return null;
        }

        $messages = Database::connection()->prepare(
            'SELECT dm.id, dm.sender_user_id, dm.body, dm.read_at, dm.created_at, u.display_name AS sender_name
             FROM direct_messages dm INNER JOIN users u ON u.id=dm.sender_user_id
             WHERE dm.thread_id=:thread_id ORDER BY dm.id ASC LIMIT 500'
        );
        $messages->execute(['thread_id' => $threadId]);
        $thread['messages'] = $messages->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $read = Database::connection()->prepare(
            'UPDATE direct_messages SET read_at=NOW() WHERE thread_id=:thread_id AND sender_user_id<>:user_id AND read_at IS NULL'
        );
        $read->execute(['thread_id'=>$threadId,'user_id'=>$userId]);
        return $thread;
    }

    public static function otherUserId(int $threadId, int $userId): ?int
    {
        $statement = Database::connection()->prepare(
            'SELECT CASE WHEN user_low_id=:uid1 THEN user_high_id ELSE user_low_id END
             FROM direct_threads WHERE id=:thread_id AND (user_low_id=:uid2 OR user_high_id=:uid3) LIMIT 1'
        );
        $statement->execute(['uid1'=>$userId,'thread_id'=>$threadId,'uid2'=>$userId,'uid3'=>$userId]);
        $other = $statement->fetchColumn();
        return $other !== false ? (int) $other : null;
    }
}
