<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

final class MatchLineup
{
    public static function starterLimit(string $format): int
    {
        return match ($format) {
            '5v5' => 5, '6v6' => 6, '7v7' => 7, '8v8' => 8,
            default => 7,
        };
    }

    /** @param array<string,mixed> $payload @param array<int,int> $activeMemberIds @return array<string,string> */
    public static function validateSelection(array $payload, array $activeMemberIds, string $format): array
    {
        $errors = [];
        $starters = self::ids($payload['starters'] ?? []);
        $subs = self::ids($payload['substitutes'] ?? []);
        if (count($starters) !== self::starterLimit($format)) {
            $errors['starters'] = 'İlk kadro ' . self::starterLimit($format) . ' oyuncudan oluşmalı.';
        }
        if (count($subs) > 7) {
            $errors['substitutes'] = 'En fazla 7 yedek seçebilirsin.';
        }
        $all = array_merge($starters, $subs);
        if (count($all) !== count(array_unique($all))) {
            $errors['players'] = 'Bir oyuncu kadroda yalnızca bir kez yer alabilir.';
        }
        $allowed = array_fill_keys(array_map('intval', $activeMemberIds), true);
        foreach ($all as $id) {
            if ($id <= 0 || !isset($allowed[$id])) {
                $errors['players'] = 'Yalnızca aktif takım oyuncuları maç kadrosuna eklenebilir.';
                break;
            }
        }
        return $errors;
    }

    public static function canEdit(string $status, bool $isCaptain, int $matchTimestamp, int $now): bool
    {
        return $isCaptain && $status === 'confirmed' && $now < $matchTimestamp;
    }

    /** @param array<int,array<string,mixed>> $rows */
    public static function isCompleteRows(array $rows, int $homeTeamId, int $awayTeamId, string $format): bool
    {
        $required = self::starterLimit($format);
        $counts = [$homeTeamId => 0, $awayTeamId => 0];
        foreach ($rows as $row) {
            $teamId = (int) ($row['team_id'] ?? 0);
            if (isset($counts[$teamId]) && ($row['role'] ?? '') === 'starter') {
                $counts[$teamId]++;
            }
        }
        return $counts[$homeTeamId] === $required && $counts[$awayTeamId] === $required;
    }

    public static function isMatchComplete(int $matchId, int $homeTeamId, int $awayTeamId, string $format): bool
    {
        return self::isCompleteRows(self::forMatch($matchId), $homeTeamId, $awayTeamId, $format);
    }

    public static function availablePlayers(int $matchId, int $captainUserId): ?array
    {
        $match = self::matchForCaptain($matchId, $captainUserId);
        if ($match === null) return null;
        $side = FootballMatch::managerSide($match, $captainUserId);
        $teamId = $side === 'home' ? (int)$match['home_team_id'] : (int)$match['away_team_id'];
        $stmt = Database::connection()->prepare(
            'SELECT u.id AS user_id,u.display_name,pp.primary_position,tm.role AS team_role
             FROM team_members tm INNER JOIN users u ON u.id=tm.user_id
             LEFT JOIN player_profiles pp ON pp.user_id=u.id
             WHERE tm.team_id=:team_id AND tm.status="active" AND u.status="active"
             ORDER BY FIELD(tm.role,"captain","vice_captain","player"),u.display_name'
        );
        $stmt->execute(['team_id'=>$teamId]);
        $match['side']=$side; $match['team_id']=$teamId; $match['available_players']=$stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $match['lineup']=self::forTeam($matchId,$teamId);
        return $match;
    }

    /** @param array<string,mixed> $payload */
    public static function save(int $matchId, int $captainUserId, array $payload): ?array
    {
        $pdo=Database::connection(); $pdo->beginTransaction();
        try {
            $match=self::matchForCaptain($matchId,$captainUserId,true);
            if ($match===null) { $pdo->rollBack(); return null; }
            $side=FootballMatch::managerSide($match,$captainUserId);
            $matchAt=strtotime((string)$match['match_at']) ?: 0;
            if ($side===null || !self::canEdit((string)$match['status'],true,$matchAt,time())) { $pdo->rollBack(); return null; }
            $teamId=$side==='home'?(int)$match['home_team_id']:(int)$match['away_team_id'];
            $members=$pdo->prepare('SELECT user_id FROM team_members WHERE team_id=:team_id AND status="active"');
            $members->execute(['team_id'=>$teamId]);
            $memberIds=array_map('intval',array_column($members->fetchAll(PDO::FETCH_ASSOC) ?: [],'user_id'));
            $errors=self::validateSelection($payload,$memberIds,(string)$match['match_format']);
            if ($errors!==[]) throw new RuntimeException((string)reset($errors));
            $starters=self::ids($payload['starters'] ?? []); $subs=self::ids($payload['substitutes'] ?? []);
            $pdo->prepare('DELETE FROM match_lineups WHERE match_id=:match_id AND team_id=:team_id')->execute(['match_id'=>$matchId,'team_id'=>$teamId]);
            $pdo->prepare('DELETE FROM match_players WHERE match_id=:match_id AND team_id=:team_id')->execute(['match_id'=>$matchId,'team_id'=>$teamId]);
            $insert=$pdo->prepare('INSERT INTO match_lineups (match_id,user_id,team_id,role,sort_order,selected_by_user_id) VALUES (:match_id,:user_id,:team_id,:role,:sort_order,:selected_by)');
            $compat=$pdo->prepare('INSERT INTO match_players (match_id,user_id,team_id) VALUES (:match_id,:user_id,:team_id)');
            $order=1;
            foreach ([['starter',$starters],['substitute',$subs]] as [$role,$ids]) {
                foreach ($ids as $uid) {
                    $insert->execute(['match_id'=>$matchId,'user_id'=>$uid,'team_id'=>$teamId,'role'=>$role,'sort_order'=>$order++,'selected_by'=>$captainUserId]);
                    $compat->execute(['match_id'=>$matchId,'user_id'=>$uid,'team_id'=>$teamId]);
                }
            }
            $pdo->commit();
            return ['match_id'=>$matchId,'team_id'=>$teamId,'side'=>$side,'count'=>count($starters)+count($subs)];
        } catch (Throwable $e) { if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }

    public static function forTeam(int $matchId,int $teamId): array
    {
        $stmt=Database::connection()->prepare('SELECT ml.*,u.display_name,pp.primary_position FROM match_lineups ml INNER JOIN users u ON u.id=ml.user_id LEFT JOIN player_profiles pp ON pp.user_id=u.id WHERE ml.match_id=:match_id AND ml.team_id=:team_id ORDER BY FIELD(ml.role,"starter","substitute"),ml.sort_order,u.display_name');
        $stmt->execute(['match_id'=>$matchId,'team_id'=>$teamId]); return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function forMatch(int $matchId): array
    {
        $stmt=Database::connection()->prepare('SELECT ml.*,u.display_name,pp.primary_position FROM match_lineups ml INNER JOIN users u ON u.id=ml.user_id LEFT JOIN player_profiles pp ON pp.user_id=u.id WHERE ml.match_id=:match_id ORDER BY ml.team_id,FIELD(ml.role,"starter","substitute"),ml.sort_order');
        $stmt->execute(['match_id'=>$matchId]); return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private static function matchForCaptain(int $matchId,int $captainUserId,bool $lock=false): ?array
    {
        $sql='SELECT m.*,home.captain_user_id AS home_captain_user_id,away.captain_user_id AS away_captain_user_id,home.name AS home_team_name,away.name AS away_team_name FROM matches m INNER JOIN teams home ON home.id=m.home_team_id INNER JOIN teams away ON away.id=m.away_team_id WHERE m.id=:match_id AND EXISTS (SELECT 1 FROM team_members tm WHERE tm.user_id=:captain AND tm.status="active" AND tm.role IN ("captain","vice_captain") AND tm.team_id IN (m.home_team_id,m.away_team_id)) LIMIT 1'.($lock?' FOR UPDATE':'');
        $stmt=Database::connection()->prepare($sql); $stmt->execute(['match_id'=>$matchId,'captain'=>$captainUserId]); $row=$stmt->fetch(PDO::FETCH_ASSOC); return is_array($row)?$row:null;
    }

    private static function ids(mixed $value): array
    {
        if(!is_array($value)) return [];
        return array_values(array_map('intval',array_filter($value,static fn($v)=>is_scalar($v) && (int)$v>0)));
    }
}
