<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

final class TeamStats
{
    /** @return array{played:int,wins:int,draws:int,losses:int,goals_for:int,goals_against:int,last_five:list<string>,recent_matches:list<array<string,mixed>>} */
    public static function forTeam(int $teamId): array
    {
        $empty=['played'=>0,'wins'=>0,'draws'=>0,'losses'=>0,'goals_for'=>0,'goals_against'=>0,'last_five'=>[],'recent_matches'=>[]];
        try{
            $stmt=Database::connection()->prepare(
                'SELECT m.id,m.home_team_id,m.away_team_id,m.home_score,m.away_score,m.match_at,m.status,ht.name AS home_name,at.name AS away_name
                 FROM matches m
                 INNER JOIN teams ht ON ht.id=m.home_team_id
                 INNER JOIN teams at ON at.id=m.away_team_id
                 WHERE m.status="verified" AND (m.home_team_id=:team_id OR m.away_team_id=:team_id2)
                 ORDER BY m.match_at DESC,m.id DESC LIMIT 100'
            );
            $stmt->execute(['team_id'=>$teamId,'team_id2'=>$teamId]);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
        }catch(Throwable){return $empty;}
        $out=$empty;
        foreach($rows as $index=>$row){
            $home=(int)$row['home_team_id']===$teamId;
            $gf=(int)($home?$row['home_score']:$row['away_score']);
            $ga=(int)($home?$row['away_score']:$row['home_score']);
            $out['played']++;$out['goals_for']+=$gf;$out['goals_against']+=$ga;
            $result=$gf>$ga?'G':($gf===$ga?'B':'M');
            if($result==='G')$out['wins']++;elseif($result==='B')$out['draws']++;else$out['losses']++;
            if($index<5)$out['last_five'][]=$result;
            if($index<5)$out['recent_matches'][]=['id'=>(int)$row['id'],'match_at'=>$row['match_at'],'goals_for'=>$gf,'goals_against'=>$ga,'result'=>$result,'opponent'=>$home?(string)($row['away_name']??'Rakip'):(string)($row['home_name']??'Rakip')];
        }
        return $out;
    }

    /** @param list<array<string,mixed>> $roster @return array<int,array<string,mixed>> keyed by user id */
    public static function forRoster(int $teamId,array $roster): array
    {
        $result=[];
        foreach($roster as $member){
            $uid=(int)($member['id']??0);if($uid<=0)continue;
            $result[$uid]=['appearances'=>0,'starts'=>0,'goals'=>0,'assists'=>0,'mvps'=>0,'average_rating'=>0.0,'last_match_at'=>null,'form_score'=>0,'form_label'=>'Veri Bekleniyor'];
        }
        if($result===[])return $result;
        try{
            $stmt=Database::connection()->prepare(
                'SELECT mp.user_id,
                        COUNT(DISTINCT mp.match_id) AS appearances,
                        COALESCE(SUM(CASE WHEN ml.role="starter" THEN 1 ELSE 0 END),0) AS starts,
                        COALESCE(SUM(CASE WHEN pms.verified=1 THEN pms.goals ELSE 0 END),0) AS goals,
                        COALESCE(SUM(CASE WHEN pms.verified=1 THEN pms.assists ELSE 0 END),0) AS assists,
                        COALESCE(SUM(CASE WHEN mpr.is_finalized=1 AND mpr.is_mvp=1 THEN 1 ELSE 0 END),0) AS mvps,
                        COALESCE(AVG(CASE WHEN mpr.is_finalized=1 THEN mpr.final_rating END),0) AS average_rating,
                        MAX(m.match_at) AS last_match_at
                 FROM match_players mp
                 INNER JOIN matches m ON m.id=mp.match_id AND m.status="verified"
                 LEFT JOIN match_lineups ml ON ml.match_id=mp.match_id AND ml.user_id=mp.user_id AND ml.team_id=mp.team_id
                 LEFT JOIN player_match_stats pms ON pms.match_id=mp.match_id AND pms.user_id=mp.user_id
                 LEFT JOIN match_player_ratings mpr ON mpr.match_id=mp.match_id AND mpr.user_id=mp.user_id AND mpr.team_id=mp.team_id
                 WHERE mp.team_id=:team_id
                 GROUP BY mp.user_id'
            );
            $stmt->execute(['team_id'=>$teamId]);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
        }catch(Throwable){return $result;}
        foreach($rows as $row){
            $uid=(int)$row['user_id'];if(!isset($result[$uid]))continue;
            $last=(string)($row['last_match_at']??'');$days=9999;
            if($last!==''){$ts=strtotime($last);if($ts!==false)$days=max(0,(int)floor((time()-$ts)/86400));}
            $value=PlayerValue::calculate([
                'season_rating'=>(float)$row['average_rating'],
                'season_matches'=>(int)$row['appearances'],
                'goals'=>(int)$row['goals'],
                'assists'=>(int)$row['assists'],
                'season_mvps'=>(int)$row['mvps'],
                'days_since_last_match'=>$days,
            ]);
            $result[$uid]=[
                'appearances'=>(int)$row['appearances'],'starts'=>(int)$row['starts'],'goals'=>(int)$row['goals'],'assists'=>(int)$row['assists'],'mvps'=>(int)$row['mvps'],
                'average_rating'=>(float)$row['average_rating'],'last_match_at'=>$row['last_match_at'],'form_score'=>(int)$value['score'],'form_label'=>(string)$value['label'],
            ];
        }
        return $result;
    }

    /** @param list<array<string,mixed>> $roster */
    public static function leaders(array $roster): array
    {
        $by=function(string $key) use($roster): ?array{
            if($roster===[])return null;$items=$roster;
            usort($items,fn($a,$b)=>(int)($b[$key]??0)<=>(int)($a[$key]??0));
            return (int)($items[0][$key]??0)>0?$items[0]:null;
        };
        return ['top_scorer'=>$by('goals'),'assist_leader'=>$by('assists'),'appearance_leader'=>$by('appearances')];
    }
}
