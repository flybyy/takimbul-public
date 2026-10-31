<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class MatchOffer
{
    public static function canOffer(int $postTeamId, int $offeredByTeamId): bool
    {
        return $postTeamId > 0 && $offeredByTeamId > 0 && $postTeamId !== $offeredByTeamId;
    }

    public static function canTransition(string $current, string $target): bool
    {
        return $current === 'pending' && in_array($target, ['accepted', 'rejected', 'withdrawn'], true);
    }

    public static function matchPayload(array $post, int $offeredByTeamId): array
    {
        return [
            'home_team_id' => (int) $post['team_id'],
            'away_team_id' => $offeredByTeamId,
            'match_at' => (string) $post['match_at'],
            'province' => (string) $post['province'],
            'district' => (string) $post['district'],
            'venue' => ($post['venue'] ?? null) !== '' ? ($post['venue'] ?? null) : null,
            'match_format' => (string) $post['match_format'],
            'source_opponent_post_id' => (int) $post['id'],
        ];
    }

    public static function create(int $postId, int $offeredByTeamId): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO match_offers (opponent_post_id, offered_by_team_id) VALUES (:post_id, :team_id)'
        );
        try {
            $statement->execute(['post_id' => $postId, 'team_id' => $offeredByTeamId]);
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                throw new RuntimeException('Bu takım bu ilana daha önce teklif gönderdi.');
            }
            throw $e;
        }
        return (int) Database::connection()->lastInsertId();
    }

    public static function incomingForCaptain(int $captainUserId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT o.*, p.match_at, p.province, p.district, p.venue, p.level, p.match_format,
                    owner.name AS owner_team_name, challenger.name AS challenger_team_name,
                    challenger.id AS challenger_team_id, challenger.captain_user_id AS challenger_captain_user_id
             FROM match_offers o
             INNER JOIN opponent_posts p ON p.id = o.opponent_post_id
             INNER JOIN teams owner ON owner.id = p.team_id
             INNER JOIN teams challenger ON challenger.id = o.offered_by_team_id
             INNER JOIN team_members manager ON manager.team_id=owner.id AND manager.user_id=:captain_user_id AND manager.status="active" AND manager.role IN ("captain","vice_captain")
             ORDER BY FIELD(o.status, "pending", "accepted", "rejected", "withdrawn"), o.created_at DESC'
        );
        $statement->execute(['captain_user_id' => $captainUserId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function outgoingForCaptain(int $captainUserId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT o.*, p.match_at, p.province, p.district, p.venue, p.level, p.match_format,
                    owner.name AS owner_team_name, challenger.name AS challenger_team_name
             FROM match_offers o
             INNER JOIN opponent_posts p ON p.id = o.opponent_post_id
             INNER JOIN teams owner ON owner.id = p.team_id
             INNER JOIN teams challenger ON challenger.id = o.offered_by_team_id
             INNER JOIN team_members manager ON manager.team_id=challenger.id AND manager.user_id=:captain_user_id AND manager.status="active" AND manager.role IN ("captain","vice_captain")
             ORDER BY o.created_at DESC'
        );
        $statement->execute(['captain_user_id' => $captainUserId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function accept(int $offerId, int $captainUserId): ?array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'SELECT o.id AS offer_id, o.status AS offer_status, o.offered_by_team_id,
                        p.id, p.team_id, p.match_at, p.province, p.district, p.venue, p.match_format, p.status AS post_status,
                        owner.captain_user_id AS owner_captain_user_id, owner.name AS owner_team_name,
                        challenger.captain_user_id AS challenger_captain_user_id, challenger.name AS challenger_team_name
                 FROM match_offers o
                 INNER JOIN opponent_posts p ON p.id = o.opponent_post_id
                 INNER JOIN teams owner ON owner.id = p.team_id
                 INNER JOIN teams challenger ON challenger.id = o.offered_by_team_id
                 INNER JOIN team_members manager ON manager.team_id=owner.id AND manager.user_id=:captain_user_id AND manager.status="active" AND manager.role IN ("captain","vice_captain")
                 WHERE o.id = :offer_id
                 FOR UPDATE'
            );
            $statement->execute(['offer_id' => $offerId, 'captain_user_id' => $captainUserId]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row) || $row['post_status'] !== 'open' || !self::canTransition((string) $row['offer_status'], 'accepted')) {
                $pdo->rollBack();
                return null;
            }
            if (!self::canOffer((int) $row['team_id'], (int) $row['offered_by_team_id'])) {
                $pdo->rollBack();
                return null;
            }

            $payload = self::matchPayload($row, (int) $row['offered_by_team_id']);
            $insert = $pdo->prepare(
                'INSERT INTO matches (home_team_id, away_team_id, season_id, match_at, province, district, venue, match_format, source_opponent_post_id, source_match_offer_id)
                 VALUES (:home_team_id, :away_team_id, (SELECT id FROM seasons WHERE status="active" ORDER BY starts_at DESC LIMIT 1), :match_at, :province, :district, :venue, :match_format, :source_opponent_post_id, :source_match_offer_id)'
            );
            $insert->execute($payload + ['source_match_offer_id' => $offerId]);
            $matchId = (int) $pdo->lastInsertId();

            $updateOffer = $pdo->prepare('UPDATE match_offers SET status = "accepted" WHERE id = :id');
            $updateOffer->execute(['id' => $offerId]);
            $rejectOthers = $pdo->prepare('UPDATE match_offers SET status = "rejected" WHERE opponent_post_id = :post_id AND id <> :id AND status = "pending"');
            $rejectOthers->execute(['post_id' => (int) $row['id'], 'id' => $offerId]);
            $closePost = $pdo->prepare('UPDATE opponent_posts SET status = "matched" WHERE id = :post_id');
            $closePost->execute(['post_id' => (int) $row['id']]);

            $pdo->commit();
            return [
                'match_id' => $matchId,
                'challenger_captain_user_id' => (int) $row['challenger_captain_user_id'],
                'owner_team_name' => (string) $row['owner_team_name'],
                'challenger_team_name' => (string) $row['challenger_team_name'],
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function reject(int $offerId, int $captainUserId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT o.id, o.status, challenger.captain_user_id AS challenger_captain_user_id, owner.name AS owner_team_name
             FROM match_offers o
             INNER JOIN opponent_posts p ON p.id=o.opponent_post_id
             INNER JOIN teams owner ON owner.id=p.team_id
             INNER JOIN teams challenger ON challenger.id=o.offered_by_team_id
             INNER JOIN team_members manager ON manager.team_id=owner.id AND manager.user_id=:captain_user_id AND manager.status="active" AND manager.role IN ("captain","vice_captain")
             WHERE o.id=:id LIMIT 1'
        );
        $statement->execute(['id' => $offerId, 'captain_user_id' => $captainUserId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row) || !self::canTransition((string) $row['status'], 'rejected')) {
            return null;
        }
        $update = Database::connection()->prepare('UPDATE match_offers SET status="rejected" WHERE id=:id AND status="pending"');
        $update->execute(['id' => $offerId]);
        return $update->rowCount() === 1 ? $row : null;
    }

    public static function withdraw(int $offerId, int $captainUserId): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE match_offers o
             INNER JOIN team_members manager ON manager.team_id=o.offered_by_team_id AND manager.user_id=:captain_user_id AND manager.status="active" AND manager.role IN ("captain","vice_captain")
             SET o.status="withdrawn"
             WHERE o.id=:id AND o.status="pending"'
        );
        $statement->execute(['id' => $offerId, 'captain_user_id' => $captainUserId]);
        return $statement->rowCount() === 1;
    }
}
