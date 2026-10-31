<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

final class TransferOffer
{
    public static function validateInput(array $data): array
    {
        $errors = [];
        $position = trim((string)($data['proposed_position'] ?? ''));
        $jersey = trim((string)($data['proposed_jersey_number'] ?? ''));
        $message = trim((string)($data['message'] ?? ''));

        if ($position !== '' && !in_array($position, PlayerProfile::POSITIONS, true)) {
            $errors['proposed_position'] = 'Önerilen mevki geçersiz.';
        }
        if ($jersey !== '' && (!ctype_digit($jersey) || (int)$jersey < 1 || (int)$jersey > 99)) {
            $errors['proposed_jersey_number'] = 'Forma numarası 1-99 arasında olmalı.';
        }
        if (strlen($message) > 500) {
            $errors['message'] = 'Teklif mesajı 500 karakteri geçemez.';
        }
        return $errors;
    }

    public static function canTransition(string $current, string $target): bool
    {
        return $current === 'pending' && in_array($target, ['accepted','rejected','withdrawn'], true);
    }

    /** @return array{team_position:?string,jersey_number:?int,squad_status:string,jersey_warning:?string} */
    public static function resolveAcceptedMembership(array $offer, bool $jerseyAvailable): array
    {
        $position=trim((string)($offer['proposed_position'] ?? ''));
        $position=in_array($position,PlayerProfile::POSITIONS,true)?$position:null;
        $jerseyRaw=$offer['proposed_jersey_number'] ?? null;
        $jersey=is_numeric($jerseyRaw)?(int)$jerseyRaw:null;
        if($jersey!==null && ($jersey<1 || $jersey>99))$jersey=null;
        $warning=null;
        if($jersey!==null && !$jerseyAvailable){$jersey=null;$warning='Önerilen forma numarası bu arada doldu. Takıma katıldın; kaptan sana yeni bir forma numarası atayabilir.';}
        return ['team_position'=>$position,'jersey_number'=>$jersey,'squad_status'=>'reserve','jersey_warning'=>$warning];
    }

    public static function create(int $teamId, int $playerUserId, int $offeredByUserId, array $data): int
    {
        if (!Team::canManageSporting($teamId, $offeredByUserId)) {
            throw new RuntimeException('Bu takım adına transfer teklifi gönderme yetkin yok.');
        }
        if ($playerUserId <= 0 || $playerUserId === $offeredByUserId) {
            throw new RuntimeException('Geçersiz oyuncu seçimi.');
        }
        if (TeamMembership::isActiveMember($teamId, $playerUserId)) {
            throw new RuntimeException('Bu oyuncu zaten takımın aktif kadrosunda.');
        }
        $errors = self::validateInput($data);
        if ($errors !== []) {
            throw new RuntimeException((string)reset($errors));
        }
        $jersey = trim((string)($data['proposed_jersey_number'] ?? ''));
        if ($jersey !== '' && !TeamMembership::isJerseyAvailable($teamId, (int)$jersey, null)) {
            throw new RuntimeException('Bu forma numarası takımda kullanılıyor.');
        }
        $pdo = Database::connection();
        $existing = $pdo->prepare('SELECT id FROM transfer_offers WHERE team_id=:team_id AND player_user_id=:player_user_id AND status="pending" LIMIT 1');
        $existing->execute(['team_id'=>$teamId,'player_user_id'=>$playerUserId]);
        if ($existing->fetchColumn()) {
            throw new RuntimeException('Bu oyuncuya bu takım adına bekleyen bir teklif zaten var.');
        }
        $stmt = $pdo->prepare(
            'INSERT INTO transfer_offers (team_id,player_user_id,offered_by_user_id,proposed_position,proposed_jersey_number,message,status)
             VALUES (:team_id,:player_user_id,:offered_by_user_id,:proposed_position,:proposed_jersey_number,:message,"pending")'
        );
        $stmt->execute([
            'team_id'=>$teamId,
            'player_user_id'=>$playerUserId,
            'offered_by_user_id'=>$offeredByUserId,
            'proposed_position'=>trim((string)($data['proposed_position'] ?? '')) ?: null,
            'proposed_jersey_number'=>$jersey !== '' ? (int)$jersey : null,
            'message'=>trim((string)($data['message'] ?? '')) ?: null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function incomingForPlayer(int $playerUserId): array
    {
        $stmt=Database::connection()->prepare(
            'SELECT o.*,t.name AS team_name,t.province,t.district,u.display_name AS offered_by_name
             FROM transfer_offers o INNER JOIN teams t ON t.id=o.team_id INNER JOIN users u ON u.id=o.offered_by_user_id
             WHERE o.player_user_id=:user_id ORDER BY FIELD(o.status,"pending","accepted","rejected","withdrawn"),o.created_at DESC'
        );
        $stmt->execute(['user_id'=>$playerUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function outgoingForUser(int $userId): array
    {
        $stmt=Database::connection()->prepare(
            'SELECT o.*,t.name AS team_name,p.display_name AS player_name
             FROM transfer_offers o INNER JOIN teams t ON t.id=o.team_id INNER JOIN users p ON p.id=o.player_user_id
             INNER JOIN team_members tm ON tm.team_id=t.id AND tm.user_id=:user_id AND tm.status="active" AND tm.role IN ("captain","vice_captain")
             ORDER BY o.created_at DESC'
        );
        $stmt->execute(['user_id'=>$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function accept(int $offerId, int $playerUserId): ?array
    {
        $pdo=Database::connection();
        $pdo->beginTransaction();
        try {
            $stmt=$pdo->prepare(
                'SELECT o.*,t.name AS team_name,t.captain_user_id FROM transfer_offers o INNER JOIN teams t ON t.id=o.team_id
                 WHERE o.id=:id AND o.player_user_id=:player_user_id FOR UPDATE'
            );
            $stmt->execute(['id'=>$offerId,'player_user_id'=>$playerUserId]);
            $row=$stmt->fetch(PDO::FETCH_ASSOC);
            if(!is_array($row) || !self::canTransition((string)$row['status'],'accepted')) { $pdo->rollBack(); return null; }
            $proposedJersey=$row['proposed_jersey_number'] !== null ? (int)$row['proposed_jersey_number'] : null;
            $jerseyAvailable=$proposedJersey===null || TeamMembership::isJerseyAvailable((int)$row['team_id'],$proposedJersey,$playerUserId);
            $membership=self::resolveAcceptedMembership($row,$jerseyAvailable);
            $member=$pdo->prepare(
                'INSERT INTO team_members (team_id,user_id,role,status,jersey_number,team_position,squad_status,joined_at)
                 VALUES (:team_id,:user_id,"player","active",:jersey_number,:team_position,:squad_status,NOW())
                 ON DUPLICATE KEY UPDATE role=IF(role="captain","captain","player"),status="active",jersey_number=VALUES(jersey_number),team_position=VALUES(team_position),squad_status=VALUES(squad_status),joined_at=NOW()'
            );
            $member->execute(['team_id'=>(int)$row['team_id'],'user_id'=>$playerUserId,'jersey_number'=>$membership['jersey_number'],'team_position'=>$membership['team_position'],'squad_status'=>$membership['squad_status']]);
            $update=$pdo->prepare('UPDATE transfer_offers SET status="accepted",responded_at=NOW() WHERE id=:id AND status="pending"');
            $update->execute(['id'=>$offerId]);
            if($update->rowCount() !== 1){ $pdo->rollBack(); return null; }
            $pdo->commit();
            return ['team_id'=>(int)$row['team_id'],'team_name'=>(string)$row['team_name'],'captain_user_id'=>(int)$row['captain_user_id'],'offered_by_user_id'=>(int)$row['offered_by_user_id'],'jersey_warning'=>$membership['jersey_warning']];
        } catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }

    public static function reject(int $offerId, int $playerUserId): ?array
    {
        $stmt=Database::connection()->prepare('SELECT id,team_id,offered_by_user_id,status FROM transfer_offers WHERE id=:id AND player_user_id=:user_id LIMIT 1');
        $stmt->execute(['id'=>$offerId,'user_id'=>$playerUserId]); $row=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!is_array($row) || !self::canTransition((string)$row['status'],'rejected')) return null;
        $up=Database::connection()->prepare('UPDATE transfer_offers SET status="rejected",responded_at=NOW() WHERE id=:id AND status="pending"');
        $up->execute(['id'=>$offerId]); return $up->rowCount()===1?$row:null;
    }

    public static function withdraw(int $offerId, int $userId): bool
    {
        $stmt=Database::connection()->prepare(
            'UPDATE transfer_offers o INNER JOIN team_members tm ON tm.team_id=o.team_id AND tm.user_id=:user_id AND tm.status="active" AND tm.role IN ("captain","vice_captain")
             SET o.status="withdrawn",o.responded_at=NOW() WHERE o.id=:id AND o.status="pending"'
        );
        $stmt->execute(['id'=>$offerId,'user_id'=>$userId]); return $stmt->rowCount()===1;
    }
}
