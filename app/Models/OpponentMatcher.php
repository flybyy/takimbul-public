<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;use PDO;
final class OpponentMatcher
{
    public static function score(array $team,array $post,array $preferences=[]): int
    {
        $score=20;
        $tp=self::norm((string)($team['province']??''));$td=self::norm((string)($team['district']??''));$pp=self::norm((string)($post['province']??''));$pd=self::norm((string)($post['district']??''));
        if($tp!==''&&$tp===$pp)$score+=25;if($td!==''&&$td===$pd)$score+=25;
        $format=(string)($preferences['match_format']??'');if($format!==''&&$format===(string)($post['match_format']??''))$score+=12;
        $level=(string)($preferences['level']??'');if($level!==''&&$level===(string)($post['level']??''))$score+=8;
        $target=strtotime((string)($preferences['target_at']??''))?:0;$match=strtotime((string)($post['match_at']??''))?:0;
        if($target>0&&$match>0){$hours=abs($match-$target)/3600;if($hours<=2)$score+=10;elseif($hours<=12)$score+=6;elseif($hours<=24)$score+=3;}
        return max(0,min(100,$score));
    }
    public static function label(int $score): string { return $score>=90?'Çok uyumlu':($score>=60?'Uygun':($score>=40?'Değerlendirilebilir':'Düşük uyum')); }
    public static function rankedForTeam(int $teamId,array $preferences=[],int $limit=30): array
    {
        $team=Team::find($teamId);if($team===null)return[];
        $stmt=Database::connection()->prepare('SELECT p.*,t.name AS team_name,t.captain_user_id FROM opponent_posts p INNER JOIN teams t ON t.id=p.team_id WHERE p.status="open" AND p.match_at>=NOW() AND p.team_id<>:team ORDER BY p.match_at ASC LIMIT 100');$stmt->execute(['team'=>$teamId]);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
        foreach($rows as &$row){$row['compatibility_score']=self::score($team,$row,$preferences);$row['compatibility_label']=self::label((int)$row['compatibility_score']);}unset($row);
        usort($rows,static fn(array $a,array $b):int=>($b['compatibility_score']<=>$a['compatibility_score']) ?: strcmp((string)$a['match_at'],(string)$b['match_at']));return array_slice($rows,0,max(1,min(60,$limit)));
    }
    private static function norm(string $v): string { $v=strtr(trim($v),['Ç'=>'ç','Ğ'=>'ğ','İ'=>'i','I'=>'ı','Ö'=>'ö','Ş'=>'ş','Ü'=>'ü']); return strtolower($v); }
}
