<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function user(): ?array
    {
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) return null;
        if (!isset($_SESSION['_auth_checked_at']) || time()-(int)$_SESSION['_auth_checked_at']>60) {
            try {
                $stmt=Database::connection()->prepare('SELECT role,status FROM users WHERE id=:id LIMIT 1');
                $stmt->execute(['id'=>(int)$_SESSION['user']['id']]);$row=$stmt->fetch();
                if (!$row || ($row['status']??'')!=='active' || ($row['role']??'player')!=='player') { unset($_SESSION['user']); return null; }
                $_SESSION['user']['role']='player';$_SESSION['_auth_checked_at']=time();
            } catch (\Throwable) {}
        }
        return $_SESSION['user'];
    }

    public static function id(): ?int
    {
        $id = self::user()['id'] ?? null;
        return $id !== null ? (int) $id : null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        unset($_SESSION['admin'], $_SESSION['_admin_auth_checked_at']);
        $_SESSION['_auth_checked_at'] = time();
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['display_name'],
            'email' => (string) $user['email'],
            'role' => (string) ($user['role'] ?? 'player'),
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['user'], $_SESSION['_auth_checked_at']);
        session_regenerate_id(true);
    }

    public static function requireLogin(): void
    {
        if (self::id() !== null) {
            return;
        }

        $_SESSION['flash_error'] = 'Bu işlem için giriş yapmalısın.';
        header('Location: /giris');
        exit;
    }
}
