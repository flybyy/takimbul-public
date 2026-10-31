<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;
use App\Core\TurkeyLocation;
use PDO;
use RuntimeException;

final class PlayerAvailability
{
    public const POSITIONS=['Kaleci','Defans','Orta Saha','Kanat','Forvet'];

    public static function validateInput(array $data, ?int $now=null): array
    {
        $now ??= time(); $errors=[];
        $starts=(string)($data['starts_at']??''); $ends=(string)($data['ends_at']??'');
        $startTs=strtotime($starts) ?: 0; $endTs=strtotime($ends) ?: 0;
        if($startTs <= $now-300) $errors['starts_at']='Başlangıç saati geçmişte olamaz.';
        if($endTs <= $startTs) $errors['ends_at']='Bitiş saati başlangıçtan sonra olmalı.';
        if($endTs > $now + 7*86400) $errors['ends_at']='Müsaitlik en fazla 7 gün ileri planlanabilir.';
        $province=trim((string)($data['province']??'')); $district=trim((string)($data['district']??''));
        if($province==='' || TurkeyLocation::canonicalProvince($province)===null) $errors['province']='Geçerli bir il seç.';
        elseif($district==='' || !TurkeyLocation::isValidPair($province,$district)) $errors['district']='Geçerli bir ilçe seç.';
        if(!in_array((string)($data['position']??''),self::POSITIONS,true)) $errors['position']='Geçerli bir mevki seç.';
        $radius=(int)($data['radius_km']??0); if($radius<1 || $radius>100) $errors['radius_km']='Mesafe 1-100 km olmalı.';
        if(strlen(trim((string)($data['note']??'')))>280) $errors['note']='Not 280 karakteri geçemez.';
        return $errors;
    }

    public static function isActiveWindow(string $startsAt,string $endsAt,?int $now=null): bool
    { $now ??= time(); $s=strtotime($startsAt)?:0;$e=strtotime($endsAt)?:0;return $s>0&&$e>0&&$s<=$now&&$e>=$now; }

    public static function publish(int $userId,array $data): int
    {
        $errors=self::validateInput($data); if($errors!==[]) throw new RuntimeException((string)reset($errors));
        $pdo=Database::connection();$pdo->beginTransaction();
        try{
            $pdo->prepare('UPDATE player_availabilities SET status="cancelled",updated_at=NOW() WHERE user_id=:u AND status="active"')->execute(['u'=>$userId]);
            $stmt=$pdo->prepare('INSERT INTO player_availabilities (user_id,starts_at,ends_at,province,district,position,radius_km,note,status) VALUES (:u,:s,:e,:p,:d,:pos,:r,:n,"active")');
            $stmt->execute(['u'=>$userId,'s'=>date('Y-m-d H:i:s',strtotime((string)$data['starts_at'])),'e'=>date('Y-m-d H:i:s',strtotime((string)$data['ends_at'])),'p'=>TurkeyLocation::canonicalProvince((string)$data['province'])??trim((string)$data['province']),'d'=>TurkeyLocation::canonicalDistrict((string)$data['province'],(string)$data['district'])??trim((string)$data['district']),'pos'=>(string)$data['position'],'r'=>(int)$data['radius_km'],'n'=>trim((string)($data['note']??''))?:null]);
            $id=(int)$pdo->lastInsertId();$pdo->commit();return $id;
        }catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }

    public static function find(int $id): ?array
    {
        $stmt=Database::connection()->prepare('SELECT a.*,u.display_name,u.username FROM player_availabilities a INNER JOIN users u ON u.id=a.user_id WHERE a.id=:id LIMIT 1');
        $stmt->execute(['id'=>$id]);$row=$stmt->fetch(PDO::FETCH_ASSOC);return is_array($row)?$row:null;
    }

    public static function activeForUser(int $userId): ?array
    {
        $stmt=Database::connection()->prepare('SELECT * FROM player_availabilities WHERE user_id=:u AND status="active" AND ends_at>=NOW() ORDER BY created_at DESC LIMIT 1');$stmt->execute(['u'=>$userId]);$row=$stmt->fetch(PDO::FETCH_ASSOC);return is_array($row)?$row:null;
    }

    public static function cancel(int $id,int $userId): bool
    { $stmt=Database::connection()->prepare('UPDATE player_availabilities SET status="cancelled",updated_at=NOW() WHERE id=:id AND user_id=:u AND status="active"');$stmt->execute(['id'=>$id,'u'=>$userId]);return $stmt->rowCount()>0; }

    public static function browse(array $filters=[],int $limit=60): array
    {
        $where=['a.status="active"','a.ends_at>=NOW()'];$params=[];
        foreach(['province','district','position'] as $key){$v=trim((string)($filters[$key]??''));if($v!==''){$where[]='a.'.$key.'=:'.$key;$params[$key]=$v;}}
        $sql='SELECT a.*,u.display_name,u.username,p.avatar_path,p.dominant_foot,p.height_cm FROM player_availabilities a INNER JOIN users u ON u.id=a.user_id AND u.status="active" LEFT JOIN player_profiles p ON p.user_id=u.id WHERE '.implode(' AND ',$where).' ORDER BY a.starts_at ASC,a.created_at DESC LIMIT '.max(1,min(100,$limit));
        $stmt=Database::connection()->prepare($sql);$stmt->execute($params);return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    public static function createInvite(int $availabilityId,int $teamId,int $invitedBy,string $message=''): int
    {
        if(!Team::canManageSporting($teamId,$invitedBy)) throw new RuntimeException('Bu takım adına davet gönderme yetkin yok.');
        $pdo=Database::connection();
        $check=$pdo->prepare('SELECT a.user_id FROM player_availabilities a WHERE a.id=:id AND a.status="active" AND a.ends_at>=NOW() LIMIT 1');$check->execute(['id'=>$availabilityId]);$playerId=(int)($check->fetchColumn()?:0);if($playerId<=0)throw new RuntimeException('Bu müsaitlik artık aktif değil.');
        $member=$pdo->prepare('SELECT 1 FROM team_members WHERE team_id=:t AND user_id=:u AND status="active" LIMIT 1');$member->execute(['t'=>$teamId,'u'=>$playerId]);if($member->fetchColumn())throw new RuntimeException('Oyuncu zaten bu takımın kadrosunda.');
        $stmt=$pdo->prepare('INSERT INTO availability_invites (availability_id,team_id,invited_by_user_id,message,status) VALUES (:a,:t,:u,:m,"pending") ON DUPLICATE KEY UPDATE message=VALUES(message),invited_by_user_id=VALUES(invited_by_user_id),status=IF(status IN ("rejected","withdrawn"),"pending",status),created_at=IF(status IN ("rejected","withdrawn"),NOW(),created_at),responded_at=NULL');
        $stmt->execute(['a'=>$availabilityId,'t'=>$teamId,'u'=>$invitedBy,'m'=>trim($message)?:null]);
        $id=(int)$pdo->lastInsertId();if($id===0){$q=$pdo->prepare('SELECT id FROM availability_invites WHERE availability_id=:a AND team_id=:t LIMIT 1');$q->execute(['a'=>$availabilityId,'t'=>$teamId]);$id=(int)$q->fetchColumn();}return $id;
    }

    public static function invitesForUser(int $userId): array
    {
        $stmt=Database::connection()->prepare('SELECT i.*,t.name AS team_name,a.starts_at,a.ends_at,a.position,a.province,a.district FROM availability_invites i INNER JOIN player_availabilities a ON a.id=i.availability_id INNER JOIN teams t ON t.id=i.team_id WHERE a.user_id=:u ORDER BY i.created_at DESC LIMIT 30');$stmt->execute(['u'=>$userId]);return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    public static function respondInvite(int $inviteId,int $userId,string $status): bool
    {
        if(!in_array($status,['accepted','rejected'],true)) return false;
        $pdo=Database::connection();$pdo->beginTransaction();
        try{
            $stmt=$pdo->prepare('SELECT i.id,i.availability_id FROM availability_invites i INNER JOIN player_availabilities a ON a.id=i.availability_id WHERE i.id=:id AND a.user_id=:u AND i.status="pending" FOR UPDATE');$stmt->execute(['id'=>$inviteId,'u'=>$userId]);$row=$stmt->fetch(PDO::FETCH_ASSOC);if(!is_array($row)){$pdo->rollBack();return false;}
            $pdo->prepare('UPDATE availability_invites SET status=:s,responded_at=NOW() WHERE id=:id')->execute(['s'=>$status,'id'=>$inviteId]);
            if($status==='accepted'){$pdo->prepare('UPDATE player_availabilities SET status="matched",updated_at=NOW() WHERE id=:id')->execute(['id'=>(int)$row['availability_id']]);$pdo->prepare('UPDATE availability_invites SET status="rejected",responded_at=NOW() WHERE availability_id=:a AND id<>:id AND status="pending"')->execute(['a'=>(int)$row['availability_id'],'id'=>$inviteId]);}
            $pdo->commit();return true;
        }catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}
