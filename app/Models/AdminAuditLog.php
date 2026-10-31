<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

final class AdminAuditLog
{
    /** @var list<string> */
    private const SECRET_KEYS=['password','password_hash','new_password','csrf','_csrf','token','session','session_id','email','phone','contact','authorization'];

    /** @param array<string,mixed> $metadata @return array<string,mixed> */
    public static function sanitizeMetadata(array $metadata): array
    {
        $out=[];
        foreach($metadata as $key=>$value){
            $k=strtolower((string)$key);
            $blocked=false;foreach(self::SECRET_KEYS as $secret){if($k===$secret||str_contains($k,$secret)){ $blocked=true;break; }}
            if($blocked)continue;
            if(is_array($value)){$out[$key]=self::sanitizeMetadata($value);continue;}
            if(is_object($value))continue;
            if(is_string($value)&&strlen($value)>500)$value=substr($value,0,500);
            if(is_scalar($value)||$value===null)$out[$key]=$value;
        }
        return $out;
    }

    /** @param array<string,mixed> $metadata */
    public static function record(int $actorId,string $action,string $targetType,?int $targetId,array $metadata=[]): bool
    {
        $action=substr(trim($action),0,100);$targetType=substr(trim($targetType),0,60);
        if($action===''||$targetType==='')return false;
        try{
            $safe=self::sanitizeMetadata($metadata);
            $json=$safe===[]?null:json_encode($safe,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $stmt=Database::connection()->prepare('INSERT INTO admin_audit_log (actor_admin_id,action,target_type,target_id,metadata_json) VALUES (:actor,:action,:target_type,:target_id,:metadata)');
            $stmt->execute(['actor'=>$actorId>0?$actorId:null,'action'=>$action,'target_type'=>$targetType,'target_id'=>$targetId,'metadata'=>$json]);
            return true;
        }catch(Throwable $e){
            error_log('[TakimBul][audit] '.$e->getMessage());
            return false;
        }
    }

    /** @return array<int,array<string,mixed>> */
    public static function recent(int $limit=100): array
    {
        $limit=max(1,min(250,$limit));
        $sql='SELECT l.id,l.actor_admin_id,l.action,l.target_type,l.target_id,l.metadata_json,l.created_at,a.display_name AS actor_name,a.username AS actor_username FROM admin_audit_log l LEFT JOIN admins a ON a.id=l.actor_admin_id ORDER BY l.id DESC LIMIT '.$limit;
        try{return Database::connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC)?:[];}catch(Throwable $e){error_log('[TakimBul][audit-read] '.$e->getMessage());return [];}
    }
}
