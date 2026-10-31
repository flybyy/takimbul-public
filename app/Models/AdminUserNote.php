<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;use PDO;
final class AdminUserNote
{
    public static function add(int $userId,int $adminId,string $note): ?string
    {
        $note=trim($note);if($note===''||strlen($note)>1000)return'Not 1-1000 karakter olmalı.';
        $stmt=Database::connection()->prepare('INSERT INTO admin_user_notes (user_id,admin_id,note) VALUES (:user_id,:admin_id,:note)');
        $stmt->execute(['user_id'=>$userId,'admin_id'=>$adminId,'note'=>$note]);return null;
    }
    public static function forUser(int $userId): array
    {
        $stmt=Database::connection()->prepare('SELECT n.id,n.note,n.created_at,a.display_name admin_name,a.username admin_username FROM admin_user_notes n INNER JOIN admins a ON a.id=n.admin_id WHERE n.user_id=:id ORDER BY n.created_at DESC LIMIT 50');
        $stmt->execute(['id'=>$userId]);return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
    }
}
