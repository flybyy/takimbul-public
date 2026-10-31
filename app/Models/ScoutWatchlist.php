<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

final class ScoutWatchlist
{
    public static function canUseFromStatus(?string $status): bool { return $status==='approved'; }

    private static function approvedAccount(int $userId): ?array
    {
        $account=ScoutAccount::forUser($userId);
        return is_array($account) && self::canUseFromStatus($account['status']??null)?$account:null;
    }

    public static function contains(int $scoutUserId,int $playerUserId): bool
    {
        $account=self::approvedAccount($scoutUserId); if($account===null)return false;
        $stmt=Database::connection()->prepare('SELECT 1 FROM scout_watchlists WHERE scout_account_id=:scout_id AND player_user_id=:player_id LIMIT 1');
        $stmt->execute(['scout_id'=>(int)$account['id'],'player_id'=>$playerUserId]);return (bool)$stmt->fetchColumn();
    }

    public static function toggle(int $scoutUserId,int $playerUserId): bool
    {
        $account=self::approvedAccount($scoutUserId);if($account===null)throw new RuntimeException('Takip listesi yalnızca doğrulanmış scout/kulüp hesaplarına açıktır.');
        if($playerUserId<=0 || $playerUserId===$scoutUserId)throw new RuntimeException('Geçersiz oyuncu.');
        $pdo=Database::connection();
        $check=$pdo->prepare('SELECT 1 FROM scout_watchlists WHERE scout_account_id=:scout_id AND player_user_id=:player_id LIMIT 1');
        $check->execute(['scout_id'=>(int)$account['id'],'player_id'=>$playerUserId]);
        if($check->fetchColumn()){
            $del=$pdo->prepare('DELETE FROM scout_watchlists WHERE scout_account_id=:scout_id AND player_user_id=:player_id');$del->execute(['scout_id'=>(int)$account['id'],'player_id'=>$playerUserId]);return false;
        }
        $ins=$pdo->prepare('INSERT INTO scout_watchlists (scout_account_id,player_user_id) VALUES (:scout_id,:player_id)');$ins->execute(['scout_id'=>(int)$account['id'],'player_id'=>$playerUserId]);return true;
    }

    public static function forScoutUser(int $scoutUserId): array
    {
        $account=self::approvedAccount($scoutUserId);if($account===null)return [];
        $stmt=Database::connection()->prepare(
            'SELECT u.id,u.display_name,u.province,u.district,p.primary_position,w.created_at
             FROM scout_watchlists w INNER JOIN users u ON u.id=w.player_user_id INNER JOIN player_profiles p ON p.user_id=u.id
             WHERE w.scout_account_id=:scout_id AND u.status="active" AND p.visibility="public" ORDER BY w.created_at DESC'
        );
        $stmt->execute(['scout_id'=>(int)$account['id']]);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach($rows as &$row){$career=Career::summary((int)$row['id']);$value=PlayerValue::forUser((int)$row['id']);$row['season_rating']=$career['season_rating']??0;$row['goals']=$career['goals']??0;$row['assists']=$career['assists']??0;$row['mvps']=$career['season_mvps']??0;$row['value_score']=$value['score'];$row['value_label']=$value['label'];}
        unset($row);return $rows;
    }
}
