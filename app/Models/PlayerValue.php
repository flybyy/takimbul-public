<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class PlayerValue
{
    public static function calculate(array $input): array
    {
        $rating=max(0.0,min(10.0,(float)($input['season_rating'] ?? 0)));
        $matches=max(0,(int)($input['season_matches'] ?? 0));
        $goals=max(0,(int)($input['goals'] ?? 0));
        $assists=max(0,(int)($input['assists'] ?? 0));
        $mvps=max(0,(int)($input['season_mvps'] ?? 0));
        $days=array_key_exists('days_since_last_match',$input)?max(0,(int)$input['days_since_last_match']):9999;

        if($matches===0 || $rating<=0){
            return ['score'=>0,'label'=>'Veri Bekleniyor','breakdown'=>['performance'=>0.0,'consistency'=>0.0,'contribution'=>0.0,'mvp'=>0.0,'activity'=>0.0]];
        }
        $performance=round(($rating/10)*40,1);
        $consistency=round(min(1,$matches/20)*20,1);
        $rate=($goals+$assists)/max(1,$matches);
        $contribution=round(min(1,$rate/1.5)*15,1);
        $mvp=round(min(1,$mvps/5)*15,1);
        $activityBase=$days<=14?100:($days<=30?80:($days<=60?50:($days<=90?20:0)));
        $activity=round(($activityBase/100)*10,1);
        $score=(int)round($performance+$consistency+$contribution+$mvp+$activity);
        return ['score'=>max(0,min(100,$score)),'label'=>self::label($score),'breakdown'=>compact('performance','consistency','contribution','mvp','activity')];
    }

    public static function label(int $score): string
    {
        return match(true){
            $score>=90=>'Elit Form',
            $score>=80=>'Çok İyi Form',
            $score>=65=>'İyi Form',
            $score>=50=>'Yükselişte',
            $score>0=>'Gelişiyor',
            default=>'Veri Bekleniyor',
        };
    }

    public static function forUser(int $userId): array
    {
        $stmt=Database::connection()->prepare(
            'SELECT COUNT(*) AS season_matches,COALESCE(AVG(r.final_rating),0) AS season_rating,
                    COALESCE(SUM(CASE WHEN r.is_mvp=1 THEN 1 ELSE 0 END),0) AS season_mvps,
                    COALESCE(SUM(s.goals),0) AS goals,COALESCE(SUM(s.assists),0) AS assists,
                    MAX(m.match_at) AS last_match_at
             FROM match_player_ratings r
             INNER JOIN matches m ON m.id=r.match_id AND m.status="verified"
             INNER JOIN seasons se ON se.id=m.season_id AND se.status="active"
             LEFT JOIN player_match_stats s ON s.match_id=r.match_id AND s.user_id=r.user_id AND s.verified=1
             WHERE r.user_id=:user_id AND r.is_finalized=1'
        );
        $stmt->execute(['user_id'=>$userId]);$row=$stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $days=9999;
        if(!empty($row['last_match_at'])){$timestamp=strtotime((string)$row['last_match_at']);if($timestamp!==false)$days=max(0,(int)floor((time()-$timestamp)/86400));}
        return self::calculate([
            'season_rating'=>(float)($row['season_rating']??0),
            'season_matches'=>(int)($row['season_matches']??0),
            'goals'=>(int)($row['goals']??0),
            'assists'=>(int)($row['assists']??0),
            'season_mvps'=>(int)($row['season_mvps']??0),
            'days_since_last_match'=>$days,
        ]);
    }
}
