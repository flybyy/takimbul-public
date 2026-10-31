<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

final class FootballIdentity
{
    public const TYPES=['recreational_player','amateur_player','referee','coach','club_official'];
    public const STATUSES=['draft','pending','active','suspended'];

    public static function normalizeTypes(array|string $types): array
    {
        if(is_string($types))$types=[$types];
        $out=[];
        foreach($types as $type){
            $type=trim((string)$type);
            if(in_array($type,self::TYPES,true) && !in_array($type,$out,true))$out[]=$type;
        }
        return $out!==[]?$out:['recreational_player'];
    }

    public static function isValidType(string $type): bool{return in_array($type,self::TYPES,true);}

    public static function label(string $type): string
    {
        return match($type){
            'recreational_player'=>'Halı Saha Oyuncusu',
            'amateur_player'=>'Amatör Futbolcu',
            'referee'=>'Hakem',
            'coach'=>'Teknik Direktör / Antrenör',
            'club_official'=>'Takım / Kulüp Yetkilisi',
            default=>'Futbol Kimliği',
        };
    }

    public static function onboardingPathForType(string $type): string
    {
        return match($type){
            'amateur_player'=>'/amator-ilanim',
            'referee'=>'/hakem-profilim',
            'coach'=>'/teknik-direktor-profilim',
            'club_official'=>'/takim-olustur',
            default=>'/baslangic',
        };
    }

    public static function syncForUser(int $userId,array|string $types,?string $primary=null): void
    {
        if($userId<=0)return;
        $types=self::normalizeTypes($types);
        if($primary===null || !in_array($primary,$types,true))$primary=$types[0];
        $pdo=Database::connection();
        $stmt=$pdo->prepare('INSERT INTO user_football_identities (user_id,identity_type,status,is_primary,completed_at) VALUES (:user_id,:identity_type,:status,:is_primary,:completed_at) ON DUPLICATE KEY UPDATE is_primary=VALUES(is_primary),updated_at=NOW()');
        foreach($types as $type){
            $active=$type==='recreational_player';
            $stmt->execute([
                'user_id'=>$userId,
                'identity_type'=>$type,
                'status'=>$active?'active':'draft',
                'is_primary'=>$type===$primary?1:0,
                'completed_at'=>$active?date('Y-m-d H:i:s'):null,
            ]);
        }
    }

    public static function addForUser(int $userId,string $type): bool
    {
        if($userId<=0 || !self::isValidType($type))return false;
        self::syncForUser($userId,[$type],$type);
        return true;
    }

    public static function forUser(int $userId): array
    {
        try{
            $stmt=Database::connection()->prepare('SELECT id,user_id,identity_type,status,is_primary,completed_at,created_at,updated_at FROM user_football_identities WHERE user_id=:user_id ORDER BY is_primary DESC,id ASC');
            $stmt->execute(['user_id'=>$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
        }catch(Throwable){return[];}
    }

    public static function has(int $userId,string $type,?string $status=null): bool
    {
        if(!self::isValidType($type))return false;
        try{
            $sql='SELECT 1 FROM user_football_identities WHERE user_id=:user_id AND identity_type=:identity_type';
            $params=['user_id'=>$userId,'identity_type'=>$type];
            if($status!==null){$sql.=' AND status=:status';$params['status']=$status;}
            $sql.=' LIMIT 1';
            $stmt=Database::connection()->prepare($sql);$stmt->execute($params);return (bool)$stmt->fetchColumn();
        }catch(Throwable){return false;}
    }

    public static function setStatus(int $userId,string $type,string $status,bool $completed=false): void
    {
        if(!self::isValidType($type)||!in_array($status,self::STATUSES,true))return;
        $stmt=Database::connection()->prepare('UPDATE user_football_identities SET status=:status,completed_at=CASE WHEN :completed=1 THEN COALESCE(completed_at,NOW()) ELSE completed_at END,updated_at=NOW() WHERE user_id=:user_id AND identity_type=:identity_type');
        $stmt->execute(['status'=>$status,'completed'=>$completed?1:0,'user_id'=>$userId,'identity_type'=>$type]);
    }

    public static function nextOnboardingPath(int $userId): string
    {
        $rows=self::forUser($userId);
        foreach($rows as $row){
            if((string)($row['status']??'')!=='active' || empty($row['completed_at']))return self::onboardingPathForType((string)$row['identity_type']);
        }
        return '/baslangic';
    }
}
