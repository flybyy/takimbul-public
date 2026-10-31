<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Leaderboard
{
    /** @param array<int,array<string,mixed>> $rows */
    public static function filterScope(array $rows,string $province='',string $district=''): array
    {
        return array_values(array_filter($rows,static fn(array $r): bool => ($province==='' || (string)($r['province']??'')===$province) && ($district==='' || (string)($r['district']??'')===$district)));
    }

    /** @param array<int,array<string,mixed>> $rows */
    public static function aggregateRows(array $rows,string $sort='rating'): array
    {
        $players=[];
        foreach($rows as $r){
            if(($r['match_status']??'')!=='verified' || (int)($r['stats_verified']??0)!==1 || (isset($r['is_finalized']) && (int)$r['is_finalized']!==1)) continue;
            $id=(int)$r['user_id'];
            if(!isset($players[$id])) $players[$id]=['user_id'=>$id,'display_name'=>(string)($r['display_name']??''),'province'=>(string)($r['province']??''),'district'=>(string)($r['district']??''),'profile_verified_at'=>$r['profile_verified_at']??null,'matches'=>0,'rating_sum'=>0.0,'rating'=>0.0,'goals'=>0,'assists'=>0,'mvps'=>0];
            $players[$id]['matches']++; $players[$id]['rating_sum']+=(float)($r['final_rating']??0); $players[$id]['goals']+=(int)($r['goals']??0); $players[$id]['assists']+=(int)($r['assists']??0); $players[$id]['mvps']+=(int)($r['is_mvp']??0);
        }
        foreach($players as &$p){ $p['rating']=$p['matches']>0?round($p['rating_sum']/$p['matches'],1):0.0; unset($p['rating_sum']); } unset($p);
        $key=in_array($sort,['rating','goals','assists','mvps','matches'],true)?$sort:'rating';
        usort($players,static function(array $a,array $b) use($key): int { $cmp=($b[$key]??0)<=>($a[$key]??0); if($cmp!==0)return $cmp; $cmp=($b['rating']??0)<=>($a['rating']??0); if($cmp!==0)return $cmp; return strcmp((string)$a['display_name'],(string)$b['display_name']); });
        return array_values($players);
    }

    public static function rows(int $seasonId,string $province='',string $district='',string $sort='rating'): array
    {
        $sql='SELECT r.user_id,u.display_name,u.province,u.district,u.profile_verified_at,r.match_id,r.final_rating,r.is_mvp,r.is_finalized,COALESCE(s.goals,0) goals,COALESCE(s.assists,0) assists,COALESCE(s.verified,0) stats_verified,m.status match_status FROM match_player_ratings r INNER JOIN users u ON u.id=r.user_id INNER JOIN matches m ON m.id=r.match_id LEFT JOIN player_match_stats s ON s.match_id=r.match_id AND s.user_id=r.user_id WHERE m.season_id=:season_id';
        $params=['season_id'=>$seasonId]; if($province!==''){ $sql.=' AND u.province=:province'; $params['province']=$province; } if($district!==''){ $sql.=' AND u.district=:district'; $params['district']=$district; }
        $stmt=Database::connection()->prepare($sql); $stmt->execute($params); return self::aggregateRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],$sort);
    }
}
