<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ScoutAccount
{
    public const TYPES = ['scout', 'club'];

    public static function canDiscoverFromStatus(?string $status): bool
    {
        return $status === 'approved';
    }

    public static function validateApplication(array $data): array
    {
        $errors = [];
        $type = (string)($data['account_type'] ?? '');
        $organization = trim((string)($data['organization_name'] ?? ''));
        $website = trim((string)($data['website'] ?? ''));
        $note = trim((string)($data['verification_note'] ?? ''));

        if (!in_array($type, self::TYPES, true)) $errors['account_type'] = 'Hesap türü geçersiz.';
        if (strlen($organization) < 3 || strlen($organization) > 180) $errors['organization_name'] = 'Kurum adı 3-180 karakter olmalı.';
        if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) $errors['website'] = 'Web sitesi adresi geçersiz.';
        if (strlen($note) > 700) $errors['verification_note'] = 'Doğrulama açıklaması 700 karakteri geçemez.';
        return $errors;
    }

    public static function apply(int $userId, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO scout_accounts (user_id, account_type, organization_name, website, verification_note, status)
             VALUES (:user_id,:account_type,:organization_name,:website,:verification_note,"pending")
             ON DUPLICATE KEY UPDATE account_type=VALUES(account_type), organization_name=VALUES(organization_name), website=VALUES(website), verification_note=VALUES(verification_note), status="pending", reviewed_by=NULL, reviewed_by_admin_id=NULL, reviewed_at=NULL'
        );
        $stmt->execute([
            'user_id'=>$userId,
            'account_type'=>(string)$data['account_type'],
            'organization_name'=>trim((string)$data['organization_name']),
            'website'=>trim((string)($data['website'] ?? '')) ?: null,
            'verification_note'=>trim((string)($data['verification_note'] ?? '')) ?: null,
        ]);
    }

    public static function forUser(int $userId): ?array
    {
        $stmt=Database::connection()->prepare('SELECT id,user_id,account_type,organization_name,website,status,created_at,reviewed_at FROM scout_accounts WHERE user_id=:user_id LIMIT 1');
        $stmt->execute(['user_id'=>$userId]); $row=$stmt->fetch(PDO::FETCH_ASSOC); return is_array($row)?$row:null;
    }

    public static function pending(): array
    {
        $stmt=Database::connection()->query('SELECT s.*,u.display_name,u.email FROM scout_accounts s INNER JOIN users u ON u.id=s.user_id WHERE s.status="pending" ORDER BY s.created_at ASC LIMIT 200');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function review(int $id,string $status,int $adminId): void
    {
        if(!in_array($status,['approved','rejected'],true)) return;
        $stmt=Database::connection()->prepare('UPDATE scout_accounts SET status=:status,reviewed_by=NULL,reviewed_by_admin_id=:reviewed_by_admin_id,reviewed_at=NOW() WHERE id=:id');
        $stmt->execute(['status'=>$status,'reviewed_by_admin_id'=>$adminId,'id'=>$id]);
    }

    public static function canDiscover(int $userId): bool
    {
        return self::canDiscoverFromStatus(self::forUser($userId)['status'] ?? null);
    }
}
