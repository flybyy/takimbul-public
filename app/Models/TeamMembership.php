<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

final class TeamMembership
{
    public const POSITIONS=['Kaleci','Defans','Orta Saha','Kanat','Forvet'];
    public const SQUAD_STATUSES=['starter','substitute','reserve'];
    public static function validateSettings(array $data, bool $editingCaptain): array
    {
        $errors=[];
        $role=(string)($data['role'] ?? ($editingCaptain?'captain':'player'));
        $jersey=trim((string)($data['jersey_number'] ?? ''));
        $teamPosition=trim((string)($data['team_position'] ?? ''));
        $squadStatus=(string)($data['squad_status'] ?? 'reserve');
        if($editingCaptain){ if($role!=='captain') $errors['role']='Kaptan rolü bu ekrandan değiştirilemez.'; }
        elseif(!in_array($role,['player','vice_captain'],true)) $errors['role']='Bu ekrandan yalnızca oyuncu veya yardımcı kaptan rolü atanabilir.';
        if($jersey!=='' && (!ctype_digit($jersey) || (int)$jersey<1 || (int)$jersey>99)) $errors['jersey_number']='Forma numarası 1-99 arasında olmalı.';
        if($teamPosition!=='' && !in_array($teamPosition,self::POSITIONS,true)) $errors['team_position']='Takım mevkii geçersiz.';
        if(!in_array($squadStatus,self::SQUAD_STATUSES,true)) $errors['squad_status']='Kadro statüsü geçersiz.';
        return $errors;
    }

    public static function canManageSportingRole(string $role): bool
    {
        return in_array($role,['captain','vice_captain'],true);
    }

    public static function canEditMemberSettings(string $actorRole, bool $editingCaptain): bool
    {
        return $actorRole==='captain';
    }

    public static function roleForUser(int $teamId,int $userId): ?string
    {
        $stmt=Database::connection()->prepare('SELECT role FROM team_members WHERE team_id=:team_id AND user_id=:user_id AND status="active" LIMIT 1');
        $stmt->execute(['team_id'=>$teamId,'user_id'=>$userId]); $role=$stmt->fetchColumn(); return is_string($role)?$role:null;
    }

    public static function isActiveMember(int $teamId,int $userId): bool
    {
        return self::roleForUser($teamId,$userId)!==null;
    }

    public static function isJerseyAvailable(int $teamId,int $jerseyNumber,?int $exceptUserId): bool
    {
        if($jerseyNumber<1 || $jerseyNumber>99) return false;
        $sql='SELECT 1 FROM team_members WHERE team_id=:team_id AND status="active" AND jersey_number=:jersey_number';
        $params=['team_id'=>$teamId,'jersey_number'=>$jerseyNumber];
        if($exceptUserId!==null){$sql.=' AND user_id<>:except_user_id';$params['except_user_id']=$exceptUserId;}
        $sql.=' LIMIT 1';
        $stmt=Database::connection()->prepare($sql);$stmt->execute($params);return !$stmt->fetchColumn();
    }

    public static function updateSettings(int $teamId,int $actorUserId,int $memberUserId,array $data): void
    {
        $actorRole=self::roleForUser($teamId,$actorUserId);
        $team=Team::find($teamId);
        if($team===null) throw new RuntimeException('Takım bulunamadı.');
        $editingCaptain=(int)$team['captain_user_id']===$memberUserId;
        if(!self::canEditMemberSettings((string)$actorRole,$editingCaptain)) throw new RuntimeException('Bu oyuncunun takım ayarlarını değiştirme yetkin yok.');
        $errors=self::validateSettings($data,$editingCaptain); if($errors!==[]) throw new RuntimeException((string)reset($errors));
        $role=(string)($data['role'] ?? ($editingCaptain?'captain':'player'));
        $jerseyRaw=trim((string)($data['jersey_number'] ?? '')); $jersey=$jerseyRaw!==''?(int)$jerseyRaw:null;
        $teamPosition=trim((string)($data['team_position'] ?? '')); $teamPosition=$teamPosition!==''?$teamPosition:null;
        $squadStatus=(string)($data['squad_status'] ?? 'reserve');
        if($jersey!==null && !self::isJerseyAvailable($teamId,$jersey,$memberUserId)) throw new RuntimeException('Bu forma numarası takımda başka bir oyuncuda kullanılıyor.');
        $pdo=Database::connection();$pdo->beginTransaction();
        try{
            if($role==='vice_captain'){
                $demote=$pdo->prepare('UPDATE team_members SET role="player" WHERE team_id=:team_id AND role="vice_captain" AND user_id<>:user_id AND status="active"');
                $demote->execute(['team_id'=>$teamId,'user_id'=>$memberUserId]);
            }
            $up=$pdo->prepare('UPDATE team_members SET role=:role,jersey_number=:jersey_number,team_position=:team_position,squad_status=:squad_status WHERE team_id=:team_id AND user_id=:user_id AND status="active"');
            $up->execute(['role'=>$role,'jersey_number'=>$jersey,'team_position'=>$teamPosition,'squad_status'=>$squadStatus,'team_id'=>$teamId,'user_id'=>$memberUserId]);
            if($up->rowCount()===0){
                $exists=$pdo->prepare('SELECT 1 FROM team_members WHERE team_id=:team_id AND user_id=:user_id AND status="active"');
                $exists->execute(['team_id'=>$teamId,'user_id'=>$memberUserId]);
                if(!$exists->fetchColumn()) throw new RuntimeException('Oyuncu aktif takım kadrosunda değil.');
            }
            $pdo->commit();
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}
