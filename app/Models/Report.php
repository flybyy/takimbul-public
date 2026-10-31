<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Report
{
    public const TARGETS=['user','team','post','player_post','opponent_post','match','amateur_ad'];
    public const REASONS=['fake_profile','abuse','no_show','misleading','fake_stats','other'];

    public static function validate(array $data): array
    {
        $errors=[]; $target=(string)($data['target_type']??''); $id=(int)($data['target_id']??0); $reason=(string)($data['reason']??''); $details=trim((string)($data['details']??''));
        if(!in_array($target,self::TARGETS,true)) $errors['target_type']='Şikâyet türü geçersiz.';
        if($id<1) $errors['target_id']='Geçerli bir içerik seç.';
        if(!in_array($reason,self::REASONS,true)) $errors['reason']='Şikâyet nedeni geçersiz.';
        if(strlen($details)>700) $errors['details']='Açıklama 700 karakteri geçemez.';
        return $errors;
    }

    public static function create(int $reporterId,array $data): int
    {
        $stmt=Database::connection()->prepare('INSERT INTO reports (reporter_user_id,target_type,target_id,reason,details) VALUES (:reporter,:target_type,:target_id,:reason,:details)');
        $stmt->execute(['reporter'=>$reporterId,'target_type'=>$data['target_type'],'target_id'=>(int)$data['target_id'],'reason'=>$data['reason'],'details'=>trim((string)($data['details']??''))?:null]);
        return (int)Database::connection()->lastInsertId();
    }

    public static function allOpen(int $limit=100): array
    {
        $sql='SELECT r.*,u.display_name AS reporter_name FROM reports r INNER JOIN users u ON u.id=r.reporter_user_id WHERE r.status IN ("open","reviewing") ORDER BY r.created_at ASC LIMIT '.max(1,min(200,$limit));
        return Database::connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function updateStatus(int $id,string $status): void
    {
        if(!in_array($status,['reviewing','resolved','dismissed'],true)) return;
        $stmt=Database::connection()->prepare('UPDATE reports SET status=:status WHERE id=:id');$stmt->execute(['status'=>$status,'id'=>$id]);
    }
}
