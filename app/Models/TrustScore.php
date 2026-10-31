<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;use PDO;
final class TrustScore
{
    public static function fromCounts(int $verifiedMatches,int $yesCommitments,int $missedCommitments,int $lateNo): array
    {
        $verified=max(0,$verifiedMatches);$yes=max(0,$yesCommitments);$miss=max(0,$missedCommitments);$no=max(0,$lateNo);
        $base=min(70,40+min(30,$verified*2));$reliabilityTotal=$yes+$miss;
        $reliability=$reliabilityTotal>0?($yes/$reliabilityTotal):1.0;
        $score=(int)round($base+30*$reliability-min(15,$no*3));$score=max(0,min(100,$score));
        return ['score'=>$score,'verified_matches'=>$verified,'commitments_kept'=>$yes,'missed_commitments'=>$miss,'reliability_percent'=>$reliabilityTotal>0?(int)round($reliability*100):100];
    }
    public static function forUser(int $userId): array
    {
        $pdo=Database::connection();
        $q=$pdo->prepare('SELECT COUNT(DISTINCT s.match_id) FROM player_match_stats s INNER JOIN matches m ON m.id=s.match_id WHERE s.user_id=:u AND s.verified=1 AND m.status="verified"');$q->execute(['u'=>$userId]);$verified=(int)$q->fetchColumn();
        $a=$pdo->prepare('SELECT SUM(CASE WHEN a.response="yes" AND EXISTS(SELECT 1 FROM match_players mp WHERE mp.match_id=a.match_id AND mp.user_id=a.user_id) THEN 1 ELSE 0 END) kept,SUM(CASE WHEN a.response="yes" AND m.status="verified" AND NOT EXISTS(SELECT 1 FROM match_players mp WHERE mp.match_id=a.match_id AND mp.user_id=a.user_id) THEN 1 ELSE 0 END) missed FROM match_attendance a INNER JOIN matches m ON m.id=a.match_id WHERE a.user_id=:u AND m.match_at<NOW()');$a->execute(['u'=>$userId]);$row=$a->fetch(PDO::FETCH_ASSOC)?:[];
        return self::fromCounts($verified,(int)($row['kept']??0),(int)($row['missed']??0),0);
    }
}
