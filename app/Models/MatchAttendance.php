<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;use PDO;use RuntimeException;
final class MatchAttendance
{
    public static function validateResponse(string $response): ?string { return in_array($response,['yes','maybe','no'],true)?null:'Geçerli bir katılım durumu seç.'; }
    public static function canRespond(string $matchStatus,int $matchAt,?int $now=null): bool { $now??=time();return $matchStatus==='confirmed'&&$matchAt>$now; }
    public static function matchdayMode(string $status,int $matchAt,?int $now=null): string
    {
        $now??=time();if($status==='verified')return'completed';if($status==='cancelled')return'cancelled';if($status==='disputed')return'disputed';if($status==='score_pending')return'result_pending';if($matchAt<=$now)return'live';if($matchAt-$now<=21600)return'matchday';return'pre_match';
    }
    public static function setResponse(int $matchId,int $userId,string $response): bool
    {
        if(self::validateResponse($response)!==null)return false;
        $pdo=Database::connection();$stmt=$pdo->prepare('SELECT m.status,m.match_at,tm.team_id FROM matches m INNER JOIN team_members tm ON tm.user_id=:u AND tm.status="active" AND tm.team_id IN (m.home_team_id,m.away_team_id) WHERE m.id=:m LIMIT 1');$stmt->execute(['u'=>$userId,'m'=>$matchId]);$row=$stmt->fetch(PDO::FETCH_ASSOC);if(!is_array($row))return false;
        if(!self::canRespond((string)$row['status'],strtotime((string)$row['match_at'])?:0))throw new RuntimeException('Katılım durumu maç başladıktan sonra değiştirilemez.');
        $up=$pdo->prepare('INSERT INTO match_attendance (match_id,user_id,team_id,response,responded_at) VALUES (:m,:u,:t,:r,NOW()) ON DUPLICATE KEY UPDATE team_id=VALUES(team_id),response=VALUES(response),responded_at=NOW()');$up->execute(['m'=>$matchId,'u'=>$userId,'t'=>(int)$row['team_id'],'r'=>$response]);return true;
    }
    public static function forMatch(int $matchId): array
    {
        $stmt=Database::connection()->prepare('SELECT a.*,u.display_name,p.primary_position FROM match_attendance a INNER JOIN users u ON u.id=a.user_id LEFT JOIN player_profiles p ON p.user_id=u.id WHERE a.match_id=:m ORDER BY a.team_id,FIELD(a.response,"yes","maybe","no"),u.display_name');$stmt->execute(['m'=>$matchId]);return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
    }
    public static function responseForUser(int $matchId,int $userId): ?string
    { $stmt=Database::connection()->prepare('SELECT response FROM match_attendance WHERE match_id=:m AND user_id=:u LIMIT 1');$stmt->execute(['m'=>$matchId,'u'=>$userId]);$v=$stmt->fetchColumn();return is_string($v)?$v:null; }
    public static function summary(array $rows,int $homeTeamId,int $awayTeamId): array
    {
        $out=['home'=>['yes'=>0,'maybe'=>0,'no'=>0],'away'=>['yes'=>0,'maybe'=>0,'no'=>0]];
        foreach($rows as $r){$side=(int)($r['team_id']??0)===$homeTeamId?'home':((int)($r['team_id']??0)===$awayTeamId?'away':null);$resp=(string)($r['response']??'');if($side!==null&&isset($out[$side][$resp]))$out[$side][$resp]++;}return$out;
    }
}
