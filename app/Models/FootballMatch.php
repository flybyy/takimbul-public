<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class FootballMatch
{
    /** @param array<string,mixed> $match */
    public static function captainSide(array $match, int $userId): ?string
    {
        if ($userId > 0 && (int) ($match['home_captain_user_id'] ?? 0) === $userId) {
            return 'home';
        }
        if ($userId > 0 && (int) ($match['away_captain_user_id'] ?? 0) === $userId) {
            return 'away';
        }
        return null;
    }


    /** @param array<string,mixed> $match */
    public static function managerSide(array $match, int $userId): ?string
    {
        $captainSide=self::captainSide($match,$userId);
        if($captainSide!==null) return $captainSide;
        if($userId<=0) return null;
        $home=(int)($match['home_team_id'] ?? 0); $away=(int)($match['away_team_id'] ?? 0);
        if($home>0 && Team::canManageSporting($home,$userId)) return 'home';
        if($away>0 && Team::canManageSporting($away,$userId)) return 'away';
        return null;
    }

    public static function forUser(int $userId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT m.*, home.name AS home_team_name, away.name AS away_team_name
             FROM matches m
             INNER JOIN teams home ON home.id=m.home_team_id
             INNER JOIN teams away ON away.id=m.away_team_id
             WHERE (EXISTS (
                 SELECT 1 FROM team_members tm
                 WHERE tm.user_id=:user_id AND tm.status="active" AND tm.team_id IN (m.home_team_id,m.away_team_id)
             ) OR EXISTS (SELECT 1 FROM referee_match_assignments rma INNER JOIN referee_profiles rp ON rp.id=rma.referee_profile_id WHERE rma.match_id=m.id AND rp.user_id=:user_id_ref AND rma.status IN ("invited","accepted","completed")))
             ORDER BY m.match_at DESC'
        );
        $statement->execute(['user_id' => $userId,'user_id_ref'=>$userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function upcomingForUser(int $userId,int $limit=5): array
    {
        $limit=max(1,min(20,$limit));
        $stmt=Database::connection()->prepare('SELECT m.*,home.name AS home_team_name,away.name AS away_team_name FROM matches m INNER JOIN teams home ON home.id=m.home_team_id INNER JOIN teams away ON away.id=m.away_team_id WHERE m.status IN ("confirmed","score_pending","disputed") AND m.match_at>=DATE_SUB(NOW(),INTERVAL 3 HOUR) AND EXISTS(SELECT 1 FROM team_members tm WHERE tm.user_id=:u AND tm.status="active" AND tm.team_id IN (m.home_team_id,m.away_team_id)) ORDER BY m.match_at ASC LIMIT '.$limit);
        $stmt->execute(['u'=>$userId]);return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    public static function findForUser(int $matchId, int $userId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT m.*, home.name AS home_team_name, home.captain_user_id AS home_captain_user_id,
                    away.name AS away_team_name, away.captain_user_id AS away_captain_user_id
             FROM matches m
             INNER JOIN teams home ON home.id=m.home_team_id
             INNER JOIN teams away ON away.id=m.away_team_id
             WHERE m.id=:match_id AND (EXISTS (
                 SELECT 1 FROM team_members tm
                 WHERE tm.user_id=:user_id AND tm.status="active" AND tm.team_id IN (m.home_team_id,m.away_team_id)
             ) OR EXISTS (SELECT 1 FROM referee_match_assignments rma INNER JOIN referee_profiles rp ON rp.id=rma.referee_profile_id WHERE rma.match_id=m.id AND rp.user_id=:user_id_ref AND rma.status IN ("invited","accepted","completed"))) LIMIT 1'
        );
        $statement->execute(['match_id' => $matchId, 'user_id' => $userId,'user_id_ref'=>$userId]);
        $match = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($match)) {
            return null;
        }

        $roster = Database::connection()->prepare(
            'SELECT mp.team_id, u.id AS user_id, u.display_name, pp.primary_position
             FROM match_players mp
             INNER JOIN users u ON u.id=mp.user_id
             LEFT JOIN player_profiles pp ON pp.user_id=u.id
             WHERE mp.match_id=:match_id
             ORDER BY mp.team_id, u.display_name'
        );
        $roster->execute(['match_id' => $matchId]);
        $match['players'] = $roster->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $match;
    }
}
