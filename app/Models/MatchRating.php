<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

final class MatchRating
{
    /** @param array<int|string,mixed> $scores @param array<int,int> $lineupUserIds @return array<string,string> */
    public static function validateCaptainScores(array $scores,array $lineupUserIds): array
    {
        $expected=array_values(array_unique(array_map('intval',$lineupUserIds)));
        $received=[];
        foreach($scores as $uid=>$score){
            $id=(int)$uid; $received[]=$id;
            if(!is_numeric($score) || (float)$score<1.0 || (float)$score>10.0){ return ['scores'=>'Oyuncu puanları 1.0 ile 10.0 arasında olmalı.']; }
        }
        sort($expected); sort($received);
        if($expected!==$received) return ['scores'=>'Maç kadrosundaki her oyuncuya puan verilmelidir.'];
        return [];
    }

    public static function contributionScore(int $goals,int $assists,int $teamScore,int $opponentScore,string $role): float
    {
        $score=5.6 + max(0,$goals)*0.8 + max(0,$assists)*0.4;
        if($teamScore>$opponentScore) $score+=0.3; elseif($teamScore===$opponentScore) $score+=0.1;
        if($role==='starter') $score+=0.1;
        return round(max(1.0,min(10.0,$score)),1);
    }

    public static function finalRating(float $captainScore,float $contributionScore): float
    {
        return round(max(1.0,min(10.0,$captainScore*0.65+$contributionScore*0.35)),1);
    }

    /** @param array<int,array<string,mixed>> $rows */
    public static function selectMvpUserId(array $rows): ?int
    {
        if($rows===[]) return null;
        usort($rows,static function(array $a,array $b): int {
            $cmp=((float)($b['final_rating']??0)) <=> ((float)($a['final_rating']??0)); if($cmp!==0) return $cmp;
            $cmp=((int)($b['goals']??0)) <=> ((int)($a['goals']??0)); if($cmp!==0) return $cmp;
            $cmp=((int)($b['assists']??0)) <=> ((int)($a['assists']??0)); if($cmp!==0) return $cmp;
            $cmp=(($b['role']??'substitute')==='starter'?1:0) <=> (($a['role']??'substitute')==='starter'?1:0); if($cmp!==0) return $cmp;
            return ((int)($a['user_id']??PHP_INT_MAX)) <=> ((int)($b['user_id']??PHP_INT_MAX));
        });
        return (int)$rows[0]['user_id'];
    }

    public static function canRate(string $status,bool $isCaptain): bool { return $isCaptain && $status==='verified'; }

    public static function state(int $matchId): array
    {
        $stmt=Database::connection()->prepare('SELECT r.*,u.display_name,ml.role,ml.team_id,s.goals,s.assists FROM match_player_ratings r INNER JOIN users u ON u.id=r.user_id INNER JOIN match_lineups ml ON ml.match_id=r.match_id AND ml.user_id=r.user_id LEFT JOIN player_match_stats s ON s.match_id=r.match_id AND s.user_id=r.user_id WHERE r.match_id=:match_id ORDER BY ml.team_id,FIELD(ml.role,"starter","substitute"),ml.sort_order');
        $stmt->execute(['match_id'=>$matchId]); return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @param array<int|string,mixed> $scores */
    public static function submit(int $matchId,int $captainUserId,array $scores): ?array
    {
        $pdo=Database::connection(); $pdo->beginTransaction();
        try{
            $stmt=$pdo->prepare('SELECT m.*,home.captain_user_id AS home_captain_user_id,away.captain_user_id AS away_captain_user_id FROM matches m INNER JOIN teams home ON home.id=m.home_team_id INNER JOIN teams away ON away.id=m.away_team_id WHERE m.id=:id FOR UPDATE');
            $stmt->execute(['id'=>$matchId]); $match=$stmt->fetch(PDO::FETCH_ASSOC);
            if(!is_array($match)){ $pdo->rollBack(); return null; }
            $side=FootballMatch::managerSide($match,$captainUserId);
            if($side===null || !self::canRate((string)$match['status'],true) || !empty($match['ratings_finalized_at'])){ $pdo->rollBack(); return null; }
            $teamId=$side==='home'?(int)$match['home_team_id']:(int)$match['away_team_id'];
            $line=$pdo->prepare('SELECT ml.user_id,ml.role,COALESCE(s.goals,0) AS goals,COALESCE(s.assists,0) AS assists FROM match_lineups ml LEFT JOIN player_match_stats s ON s.match_id=ml.match_id AND s.user_id=ml.user_id WHERE ml.match_id=:match_id AND ml.team_id=:team_id ORDER BY ml.sort_order');
            $line->execute(['match_id'=>$matchId,'team_id'=>$teamId]); $players=$line->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if($players===[]) throw new RuntimeException('Önce maç kadrosu oluşturulmalıdır.');
            $ids=array_map('intval',array_column($players,'user_id')); $errors=self::validateCaptainScores($scores,$ids); if($errors!==[]) throw new RuntimeException((string)reset($errors));
            $teamScore=$side==='home'?(int)$match['home_score']:(int)$match['away_score']; $opponentScore=$side==='home'?(int)$match['away_score']:(int)$match['home_score'];
            $pdo->prepare('DELETE FROM match_player_ratings WHERE match_id=:match_id AND team_id=:team_id')->execute(['match_id'=>$matchId,'team_id'=>$teamId]);
            $insert=$pdo->prepare('INSERT INTO match_player_ratings (match_id,user_id,team_id,captain_score,contribution_score,final_rating,is_mvp,is_finalized,rated_by_user_id) VALUES (:match_id,:user_id,:team_id,:captain_score,:contribution_score,:final_rating,0,0,:rated_by)');
            foreach($players as $p){ $uid=(int)$p['user_id']; $captain=round((float)$scores[$uid],1); $contrib=self::contributionScore((int)$p['goals'],(int)$p['assists'],$teamScore,$opponentScore,(string)$p['role']); $insert->execute(['match_id'=>$matchId,'user_id'=>$uid,'team_id'=>$teamId,'captain_score'=>$captain,'contribution_score'=>$contrib,'final_rating'=>self::finalRating($captain,$contrib),'rated_by'=>$captainUserId]); }
            $column=$side==='home'?'home_ratings_completed_at':'away_ratings_completed_at'; $pdo->exec('UPDATE matches SET '.$column.'=NOW() WHERE id='.(int)$matchId);
            $refresh=$pdo->prepare('SELECT home_ratings_completed_at,away_ratings_completed_at FROM matches WHERE id=:id'); $refresh->execute(['id'=>$matchId]); $done=$refresh->fetch(PDO::FETCH_ASSOC) ?: [];
            $finalized=false; $mvpUserId=null;
            if(!empty($done['home_ratings_completed_at']) && !empty($done['away_ratings_completed_at'])){
                $all=$pdo->prepare('SELECT r.user_id,r.final_rating,COALESCE(s.goals,0) goals,COALESCE(s.assists,0) assists,ml.role FROM match_player_ratings r INNER JOIN match_lineups ml ON ml.match_id=r.match_id AND ml.user_id=r.user_id LEFT JOIN player_match_stats s ON s.match_id=r.match_id AND s.user_id=r.user_id WHERE r.match_id=:id');
                $all->execute(['id'=>$matchId]); $rows=$all->fetchAll(PDO::FETCH_ASSOC) ?: []; $mvpUserId=self::selectMvpUserId($rows);
                $pdo->prepare('UPDATE match_player_ratings SET is_finalized=1,is_mvp=CASE WHEN user_id=:mvp THEN 1 ELSE 0 END WHERE match_id=:id')->execute(['mvp'=>$mvpUserId,'id'=>$matchId]);
                $pdo->prepare('UPDATE matches SET ratings_finalized_at=NOW() WHERE id=:id')->execute(['id'=>$matchId]); $finalized=true;
            }
            $pdo->commit(); return ['match_id'=>$matchId,'side'=>$side,'team_id'=>$teamId,'finalized'=>$finalized,'mvp_user_id'=>$mvpUserId];
        } catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
}
