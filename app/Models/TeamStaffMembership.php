<?php

declare(strict_types=1);
namespace App\Models;

use App\Core\Database;use PDO;use RuntimeException;
final class TeamStaffMembership
{
    public const ROLES=['head_coach','assistant_coach','goalkeeper_coach','fitness_coach','analyst','other'];
    public static function roleLabel(string $role): string{return match($role){'head_coach'=>'Teknik Direktör','assistant_coach'=>'Yardımcı Antrenör','goalkeeper_coach'=>'Kaleci Antrenörü','fitness_coach'=>'Kondisyoner','analyst'=>'Analist',default=>'Teknik Ekip'};}
    public static function add(int $teamId,int $actorUserId,int $coachUserId,string $role): int
    {
        if(!Team::isCaptain($teamId,$actorUserId))throw new RuntimeException('Teknik ekip davetini yalnız takım kaptanı gönderebilir.');if(!in_array($role,self::ROLES,true))throw new RuntimeException('Teknik ekip rolü geçersiz.');$coach=CoachProfile::activeForUser($coachUserId);if(!$coach)throw new RuntimeException('Yayınlanmış teknik direktör/antrenör profili bulunamadı.');$pdo=Database::connection();$q=$pdo->prepare('SELECT id,status FROM team_staff_memberships WHERE team_id=:team AND user_id=:user AND staff_role=:role AND status IN ("invited","active") LIMIT 1');$q->execute(['team'=>$teamId,'user'=>$coachUserId,'role'=>$role]);$existing=$q->fetch(PDO::FETCH_ASSOC);if(is_array($existing))return(int)$existing['id'];$s=$pdo->prepare('INSERT INTO team_staff_memberships (team_id,user_id,coach_profile_id,staff_role,status) VALUES (:team,:user,:coach,:role,"invited")');$s->execute(['team'=>$teamId,'user'=>$coachUserId,'coach'=>(int)$coach['id'],'role'=>$role]);return(int)$pdo->lastInsertId();
    }
    public static function respond(int $membershipId,int $userId,string $decision): bool
    {
        if(!in_array($decision,['accepted','declined'],true))return false;$status=$decision==='accepted'?'active':'declined';$s=Database::connection()->prepare('UPDATE team_staff_memberships SET status=:status,joined_at=CASE WHEN :status2="active" THEN COALESCE(joined_at,NOW()) ELSE joined_at END,updated_at=NOW() WHERE id=:id AND user_id=:user AND status="invited"');$s->execute(['status'=>$status,'status2'=>$status,'id'=>$membershipId,'user'=>$userId]);return $s->rowCount()===1;
    }
    public static function leave(int $membershipId,int $actorUserId): bool
    {
        $s=Database::connection()->prepare('SELECT tsm.*,t.captain_user_id FROM team_staff_memberships tsm INNER JOIN teams t ON t.id=tsm.team_id WHERE tsm.id=:id LIMIT 1');$s->execute(['id'=>$membershipId]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!is_array($r))return false;if((int)$r['user_id']!==$actorUserId&&(int)$r['captain_user_id']!==$actorUserId)throw new RuntimeException('Bu teknik ekip üyeliğini sonlandıramazsın.');$u=Database::connection()->prepare('UPDATE team_staff_memberships SET status="left",left_at=NOW(),updated_at=NOW() WHERE id=:id AND status IN ("active","invited")');$u->execute(['id'=>$membershipId]);return $u->rowCount()===1;
    }
    public static function activeForTeam(int $teamId): array{$s=Database::connection()->prepare('SELECT tsm.*,u.display_name,u.username,u.profile_verified_at,c.slug,c.public_title,c.license_status,p.avatar_path FROM team_staff_memberships tsm INNER JOIN users u ON u.id=tsm.user_id LEFT JOIN coach_profiles c ON c.id=tsm.coach_profile_id LEFT JOIN player_profiles p ON p.user_id=u.id WHERE tsm.team_id=:team AND tsm.status="active" ORDER BY FIELD(tsm.staff_role,"head_coach","assistant_coach","goalkeeper_coach","fitness_coach","analyst","other"),u.display_name');$s->execute(['team'=>$teamId]);return$s->fetchAll(PDO::FETCH_ASSOC)?:[];}
    public static function pendingForUser(int $userId): array{$s=Database::connection()->prepare('SELECT tsm.*,t.name team_name FROM team_staff_memberships tsm INNER JOIN teams t ON t.id=tsm.team_id WHERE tsm.user_id=:user AND tsm.status="invited" ORDER BY tsm.created_at DESC');$s->execute(['user'=>$userId]);return$s->fetchAll(PDO::FETCH_ASSOC)?:[];}
}
