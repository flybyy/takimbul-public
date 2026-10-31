<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Career
{
    /** @param array<int,array<string,mixed>> $rows */
    public static function aggregateRows(array $rows): array
    {
        $matches = 0; $goals = 0; $assists = 0;
        foreach ($rows as $row) {
            if ((int)($row['verified'] ?? 0) !== 1 || ($row['match_status'] ?? '') !== 'verified') continue;
            $matches++; $goals += max(0,(int)($row['goals'] ?? 0)); $assists += max(0,(int)($row['assists'] ?? 0));
        }
        return [
            'matches'=>$matches,
            'goals'=>$goals,
            'assists'=>$assists,
            'goal_per_match'=>$matches > 0 ? round($goals/$matches,2) : 0.0,
            'contribution_per_match'=>$matches > 0 ? round(($goals+$assists)/$matches,2) : 0.0,
        ];
    }

    public static function summary(int $userId): array
    {
        $stmt=Database::connection()->prepare('SELECT s.goals,s.assists,s.verified,m.status AS match_status,m.match_at,m.id AS match_id,ht.name AS home_team,at.name AS away_team,m.home_score,m.away_score,r.final_rating,r.is_mvp,r.is_finalized,se.id AS season_id,se.name AS season_name FROM player_match_stats s INNER JOIN matches m ON m.id=s.match_id INNER JOIN teams ht ON ht.id=m.home_team_id INNER JOIN teams at ON at.id=m.away_team_id LEFT JOIN match_player_ratings r ON r.match_id=s.match_id AND r.user_id=s.user_id LEFT JOIN seasons se ON se.id=m.season_id WHERE s.user_id=:user_id ORDER BY m.match_at DESC');
        $stmt->execute(['user_id'=>$userId]); $rows=$stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $summary=self::aggregateRows($rows);
        $summary['recent_matches']=array_values(array_filter($rows,static fn(array $r)=>(int)$r['verified']===1 && $r['match_status']==='verified'));
        $current=Database::connection()->query('SELECT id,name FROM seasons WHERE status="active" ORDER BY starts_at DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: null;
        $seasonRows=array_values(array_filter($rows,static fn(array $r): bool => is_array($current) && (int)($r['season_id']??0)===(int)$current['id'] && (int)($r['verified']??0)===1 && ($r['match_status']??'')==='verified' && (int)($r['is_finalized']??0)===1));
        $ratingSum=0.0; $mvps=0; foreach($seasonRows as $row){ $ratingSum+=(float)($row['final_rating']??0); $mvps+=(int)($row['is_mvp']??0); }
        $summary['season_name']=is_array($current)?(string)$current['name']:'Aktif Sezon';
        $summary['season_matches']=count($seasonRows);
        $summary['season_rating']=$seasonRows!==[]?round($ratingSum/count($seasonRows),1):0.0;
        $summary['season_mvps']=$mvps;
        $summary['rating_history']=array_map(static fn(array $r): array => ['match_id'=>(int)$r['match_id'],'match_at'=>(string)$r['match_at'],'rating'=>(float)($r['final_rating']??0),'mvp'=>(int)($r['is_mvp']??0)],array_slice($seasonRows,0,5));
        return $summary;
    }

    public static function teams(int $userId): array
    {
        $stmt=Database::connection()->prepare('SELECT t.id,t.name,t.province,t.district,tm.role,tm.jersey_number FROM team_members tm INNER JOIN teams t ON t.id=tm.team_id WHERE tm.user_id=:user_id AND tm.status="active" AND t.status="active" ORDER BY tm.joined_at DESC');
        $stmt->execute(['user_id'=>$userId]); return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function highlights(int $userId): array
    {
        $stmt=Database::connection()->prepare('SELECT type,title,url,description,created_at FROM career_highlights WHERE user_id=:user_id ORDER BY created_at DESC LIMIT 12');
        $stmt->execute(['user_id'=>$userId]); return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
