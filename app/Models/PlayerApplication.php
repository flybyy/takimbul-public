<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;
use Throwable;

final class PlayerApplication
{
    public static function validateInput(array $data): array
    {
        $errors = [];
        $message = trim((string) ($data['message'] ?? ''));
        if (strlen($message) > 300) {
            $errors['message'] = 'Mesaj 300 karakteri geçemez.';
        }
        return $errors;
    }

    public static function canTransition(string $current, string $target, string $actor): bool
    {
        if ($current !== 'pending') {
            return false;
        }
        if ($actor === 'applicant') {
            return $target === 'withdrawn';
        }
        if ($actor === 'captain') {
            return in_array($target, ['accepted', 'rejected'], true);
        }
        return false;
    }

    public static function canAcceptIntoRoster(int $playersNeeded, int $acceptedCount): bool
    {
        return $playersNeeded > 0 && $acceptedCount < $playersNeeded;
    }

    public static function create(int $postId, int $userId, string $message = ''): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO player_wanted_applications (post_id, user_id, message) VALUES (:post_id, :user_id, :message)'
        );
        try {
            $statement->execute([
                'post_id' => $postId,
                'user_id' => $userId,
                'message' => trim($message) !== '' ? trim($message) : null,
            ]);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new \RuntimeException('Bu ilana daha önce başvurdun.');
            }
            throw $e;
        }
        return (int) Database::connection()->lastInsertId();
    }

    public static function forUser(int $userId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT a.*, p.match_at, p.position, p.province, p.district, p.venue, t.name AS team_name, t.id AS team_id
             FROM player_wanted_applications a
             INNER JOIN player_wanted_posts p ON p.id = a.post_id
             INNER JOIN teams t ON t.id = p.team_id
             WHERE a.user_id = :user_id
             ORDER BY a.created_at DESC'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function incomingForCaptain(int $captainUserId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT a.*, p.match_at, p.position, p.province, p.district, p.venue,
                    t.name AS team_name, t.id AS team_id,
                    u.display_name AS applicant_name, u.province AS applicant_province, u.district AS applicant_district,
                    pp.primary_position AS applicant_position
             FROM player_wanted_applications a
             INNER JOIN player_wanted_posts p ON p.id = a.post_id
             INNER JOIN teams t ON t.id = p.team_id
             INNER JOIN users u ON u.id = a.user_id
             LEFT JOIN player_profiles pp ON pp.user_id = a.user_id
             INNER JOIN team_members manager ON manager.team_id=t.id AND manager.user_id=:captain_user_id AND manager.status="active" AND manager.role IN ("captain","vice_captain")
             ORDER BY FIELD(a.status, "pending", "accepted", "rejected", "withdrawn"), a.created_at DESC'
        );
        $statement->execute(['captain_user_id' => $captainUserId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function respond(int $applicationId, int $captainUserId, string $targetStatus): ?array
    {
        if (!in_array($targetStatus, ['accepted', 'rejected'], true)) {
            return null;
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'SELECT a.id, a.user_id, a.status, a.post_id, p.team_id, p.match_at, p.position, p.players_needed, t.name AS team_name
                 FROM player_wanted_applications a
                 INNER JOIN player_wanted_posts p ON p.id = a.post_id
                 INNER JOIN teams t ON t.id = p.team_id
                 INNER JOIN team_members manager ON manager.team_id=t.id AND manager.user_id=:captain_user_id AND manager.status="active" AND manager.role IN ("captain","vice_captain")
                 WHERE a.id = :id
                 FOR UPDATE'
            );
            $statement->execute(['id' => $applicationId, 'captain_user_id' => $captainUserId]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row) || !self::canTransition((string) $row['status'], $targetStatus, 'captain')) {
                $pdo->rollBack();
                return null;
            }

            $acceptedCount = 0;
            if ($targetStatus === 'accepted') {
                $count = $pdo->prepare('SELECT COUNT(*) FROM player_wanted_applications WHERE post_id=:post_id AND status="accepted"');
                $count->execute(['post_id'=>(int)$row['post_id']]);
                $acceptedCount = (int)$count->fetchColumn();
                if (!self::canAcceptIntoRoster((int)$row['players_needed'], $acceptedCount)) {
                    $pdo->rollBack();
                    return null;
                }
            }

            $update = $pdo->prepare('UPDATE player_wanted_applications SET status = :status WHERE id = :id');
            $update->execute(['status' => $targetStatus, 'id' => $applicationId]);
            if ($targetStatus === 'accepted' && ($acceptedCount + 1) >= (int)$row['players_needed']) {
                $close = $pdo->prepare('UPDATE player_wanted_posts SET status="closed" WHERE id=:post_id AND status="open"');
                $close->execute(['post_id'=>(int)$row['post_id']]);
            }
            $pdo->commit();
            $row['status'] = $targetStatus;
            return $row;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function withdraw(int $applicationId, int $userId): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE player_wanted_applications SET status = "withdrawn"
             WHERE id = :id AND user_id = :user_id AND status = "pending"'
        );
        $statement->execute(['id' => $applicationId, 'user_id' => $userId]);
        return $statement->rowCount() === 1;
    }
}
