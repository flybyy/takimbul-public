<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
use App\Core\TurkeyLocation;use PDO;use RuntimeException;
final class TeamFinder
{
    public const LEVELS=['Eğlence','Orta','Rekabetçi'];
    public static function compatibilityScore(array $player,array $need): int
    {
        $score=0;
        if(self::fold((string)($player['province']??''))!=='' && self::fold((string)($player['province']??''))===self::fold((string)($need['province']??'')))$score+=30;
        if(self::fold((string)($player['district']??''))!=='' && self::fold((string)($player['district']??''))===self::fold((string)($need['district']??'')))$score+=20;
        $pp=(string)($player['primary_position']??'');$sp=(string)($player['secondary_position']??'');$np=(string)($need['position']??'');
        if($np!==''&&$pp===$np)$score+=30;elseif($np!==''&&$sp===$np)$score+=18;
        $seen=strtotime((string)($player['last_seen_at']??''));if($seen!==false){$age=time()-$seen;if($age<=86400)$score+=12;elseif($age<=604800)$score+=8;elseif($age<=2592000)$score+=4;}
        $level=(string)($player['desired_level']??'');if($level!==''&&$level===(string)($need['level']??''))$score+=8;
        return max(0,min(100,$score));
    }
    public static function validateProfile(array $data): array
    {
        $e=[];$province=trim((string)($data['province']??''));$district=trim((string)($data['district']??''));$p=(string)($data['primary_position']??'');$s=(string)($data['secondary_position']??'');$level=(string)($data['desired_level']??'');
        if($province===''||TurkeyLocation::canonicalProvince($province)===null)$e['province']='Geçerli bir il seç.';elseif($district!==''&&!TurkeyLocation::isValidPair($province,$district))$e['district']='Geçerli bir ilçe seç.';
        if($p!==''&&!in_array($p,PlayerProfile::POSITIONS,true))$e['primary_position']='Geçersiz mevki.';if($s!==''&&!in_array($s,PlayerProfile::POSITIONS,true))$e['secondary_position']='Geçersiz ikinci mevki.';
        if($level!==''&&!in_array($level,self::LEVELS,true))$e['desired_level']='Geçersiz seviye.';if(strlen(trim((string)($data['availability_text']??'')))>300)$e['availability_text']='Müsaitlik 300 karakteri geçemez.';if(strlen(trim((string)($data['bio']??'')))>400)$e['bio']='Açıklama 400 karakteri geçemez.';return$e;
    }
    public static function saveProfile(int $userId,array $data): void
    {
        $stmt=Database::connection()->prepare('INSERT INTO team_search_profiles (user_id,province,district,primary_position,secondary_position,desired_level,availability_text,bio,status) VALUES (:user_id,:province,:district,:primary_position,:secondary_position,:desired_level,:availability_text,:bio,:status) ON DUPLICATE KEY UPDATE province=VALUES(province),district=VALUES(district),primary_position=VALUES(primary_position),secondary_position=VALUES(secondary_position),desired_level=VALUES(desired_level),availability_text=VALUES(availability_text),bio=VALUES(bio),status=VALUES(status),updated_at=NOW()');
        $stmt->execute(['user_id'=>$userId,'province'=>trim((string)$data['province']),'district'=>self::nullEmpty($data['district']??null),'primary_position'=>self::nullEmpty($data['primary_position']??null),'secondary_position'=>self::nullEmpty($data['secondary_position']??null),'desired_level'=>self::nullEmpty($data['desired_level']??null),'availability_text'=>self::nullEmpty($data['availability_text']??null),'bio'=>self::nullEmpty($data['bio']??null),'status'=>(($data['status']??'active')==='paused'?'paused':'active')]);
    }
    public static function profile(int $userId): ?array{$s=Database::connection()->prepare('SELECT * FROM team_search_profiles WHERE user_id=:id LIMIT 1');$s->execute(['id'=>$userId]);$r=$s->fetch(PDO::FETCH_ASSOC);return is_array($r)?$r:null;}
    public static function teamsForPlayer(int $userId,array $filters=[]): array
    {
        $stmt=Database::connection()->prepare('SELECT u.province,u.district,u.last_seen_at,p.primary_position,p.secondary_position,tsp.desired_level FROM users u LEFT JOIN player_profiles p ON p.user_id=u.id LEFT JOIN team_search_profiles tsp ON tsp.user_id=u.id WHERE u.id=:id LIMIT 1');$stmt->execute(['id'=>$userId]);$player=$stmt->fetch(PDO::FETCH_ASSOC)?:[];
        $sql='SELECT p.id post_id,p.team_id,p.position,p.level,p.match_at,p.province,p.district,p.venue,p.players_needed,t.name team_name,t.logo_path FROM player_wanted_posts p INNER JOIN teams t ON t.id=p.team_id WHERE p.status="open" AND p.match_at>=NOW()';$params=[];
        foreach(['province','district','position','level'] as $k){if(trim((string)($filters[$k]??''))!==''){$sql.=' AND p.'.$k.'=:'.$k;$params[$k]=trim((string)$filters[$k]);}}
        $sql.=' ORDER BY p.match_at ASC LIMIT 80';$q=Database::connection()->prepare($sql);$q->execute($params);$rows=$q->fetchAll(PDO::FETCH_ASSOC)?:[];foreach($rows as &$r)$r['compatibility']=self::compatibilityScore($player,$r);unset($r);usort($rows,fn($a,$b)=>($b['compatibility']<=>$a['compatibility'])?:strcmp((string)$a['match_at'],(string)$b['match_at']));return$rows;
    }
    public static function playersForTeam(int $teamId,array $filters=[]): array
    {
        $team=Team::find($teamId);if(!$team)return[];$sql='SELECT tsp.*,u.display_name,u.username,u.last_seen_at,p.avatar_path,p.dominant_foot,p.birth_year FROM team_search_profiles tsp INNER JOIN users u ON u.id=tsp.user_id LEFT JOIN player_profiles p ON p.user_id=u.id WHERE tsp.status="active" AND u.status="active"';$params=[];
        if(trim((string)($filters['province']??''))!==''){$sql.=' AND tsp.province=:province';$params['province']=trim((string)$filters['province']);}if(trim((string)($filters['district']??''))!==''){$sql.=' AND tsp.district=:district';$params['district']=trim((string)$filters['district']);}if(trim((string)($filters['position']??''))!==''){$sql.=' AND (tsp.primary_position=:position_primary OR tsp.secondary_position=:position_secondary)';$params['position_primary']=trim((string)$filters['position']);$params['position_secondary']=trim((string)$filters['position']);}
        $sql.=' ORDER BY u.last_seen_at DESC,tsp.updated_at DESC LIMIT 80';$q=Database::connection()->prepare($sql);$q->execute($params);$rows=$q->fetchAll(PDO::FETCH_ASSOC)?:[];foreach($rows as &$r)$r['compatibility']=self::compatibilityScore($r,['province'=>$team['province'],'district'=>$team['district'],'position'=>(string)($filters['position']??''),'level'=>(string)($filters['level']??'')]);unset($r);usort($rows,fn($a,$b)=>$b['compatibility']<=>$a['compatibility']);return$rows;
    }
    public static function invite(int $teamId,int $actorId,int $userId,string $message=''): int
    {
        if(!Team::canManageSporting($teamId,$actorId))throw new RuntimeException('Bu takım adına davet gönderme yetkin yok.');if(TeamMembership::isActiveMember($teamId,$userId))throw new RuntimeException('Oyuncu zaten takım kadrosunda.');
        $userCheck=Database::connection()->prepare('SELECT 1 FROM users WHERE id=:id AND status="active" LIMIT 1');$userCheck->execute(['id'=>$userId]);if(!$userCheck->fetchColumn())throw new RuntimeException('Oyuncu bulunamadı veya hesabı aktif değil.');
        $pending=Database::connection()->prepare('SELECT id FROM team_invites WHERE team_id=:team_id AND user_id=:user_id AND status="pending" LIMIT 1');$pending->execute(['team_id'=>$teamId,'user_id'=>$userId]);if($pending->fetchColumn())throw new RuntimeException('Bu oyuncuya zaten bekleyen bir takım daveti var.');
        $stmt=Database::connection()->prepare('INSERT INTO team_invites (team_id,user_id,invited_by,message) VALUES (:team_id,:user_id,:invited_by,:message)');$stmt->execute(['team_id'=>$teamId,'user_id'=>$userId,'invited_by'=>$actorId,'message'=>self::nullEmpty(substr(trim($message),0,300))]);$id=(int)Database::connection()->lastInsertId();Notification::create($userId,'team_invite','Takım daveti aldın',(string)(Team::find($teamId)['name']??'Bir takım').' seni kadrosuna davet etti.','/takim-bul');return$id;
    }
    public static function incomingInvites(int $userId): array{$s=Database::connection()->prepare('SELECT i.*,t.name team_name,t.province,t.district,u.display_name invited_by_name FROM team_invites i INNER JOIN teams t ON t.id=i.team_id INNER JOIN users u ON u.id=i.invited_by WHERE i.user_id=:id AND i.status="pending" ORDER BY i.created_at DESC');$s->execute(['id'=>$userId]);return$s->fetchAll(PDO::FETCH_ASSOC)?:[];}
    public static function respond(int $inviteId,int $userId,string $status): void
    {
        if(!in_array($status,['accepted','rejected'],true))throw new RuntimeException('Geçersiz yanıt.');$pdo=Database::connection();$pdo->beginTransaction();try{$s=$pdo->prepare('SELECT * FROM team_invites WHERE id=:id AND user_id=:user_id AND status="pending" FOR UPDATE');$s->execute(['id'=>$inviteId,'user_id'=>$userId]);$i=$s->fetch(PDO::FETCH_ASSOC);if(!$i)throw new RuntimeException('Davet bulunamadı veya daha önce yanıtlandı.');$u=$pdo->prepare('UPDATE team_invites SET status=:status,responded_at=NOW() WHERE id=:id');$u->execute(['status'=>$status,'id'=>$inviteId]);if($status==='accepted'){$m=$pdo->prepare('INSERT INTO team_members (team_id,user_id,role,status) VALUES (:team_id,:user_id,"player","active") ON DUPLICATE KEY UPDATE role="player",status="active",joined_at=NOW()');$m->execute(['team_id'=>(int)$i['team_id'],'user_id'=>$userId]);}Notification::create((int)$i['invited_by'],'team_invite_response',$status==='accepted'?'Takım daveti kabul edildi':'Takım daveti reddedildi',null,'/takim/'.(int)$i['team_id']);$pdo->commit();}catch(\Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw$e;}
    }
    private static function fold(string $v): string{$map=['İ'=>'i','I'=>'i','ı'=>'i','Ş'=>'s','ş'=>'s','Ğ'=>'g','ğ'=>'g','Ü'=>'u','ü'=>'u','Ö'=>'o','ö'=>'o','Ç'=>'c','ç'=>'c'];return strtolower(strtr(trim($v),$map));}
    private static function nullEmpty(mixed $v): mixed{$v=trim((string)$v);return$v===''?null:$v;}
}
