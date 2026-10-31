<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Admin;
use App\Services\AdminPermission;

final class AdminAuth
{
    public static function admin(): ?array
    {
        if(!isset($_SESSION['admin']) || !is_array($_SESSION['admin'])) return null;
        if(!isset($_SESSION['_admin_auth_checked_at']) || time()-(int)$_SESSION['_admin_auth_checked_at']>60){
            try{
                $fresh=Admin::find((int)($_SESSION['admin']['id']??0));
                if(!$fresh || ($fresh['status']??'')!=='active'){
                    unset($_SESSION['admin'],$_SESSION['_admin_auth_checked_at']);
                    return null;
                }
                $_SESSION['admin']['name']=(string)$fresh['display_name'];
                $_SESSION['admin']['username']=(string)$fresh['username'];
                $_SESSION['admin']['email']=(string)$fresh['email'];
                $_SESSION['admin']['role']=(string)($fresh['role']??'writer');
                $_SESSION['_admin_auth_checked_at']=time();
            }catch(\Throwable){}
        }
        return $_SESSION['admin'];
    }

    public static function id(): ?int
    {
        $id=self::admin()['id']??null;
        return $id!==null?(int)$id:null;
    }

    public static function role(): ?string
    {
        $role=self::admin()['role']??null;
        return $role!==null?(string)$role:null;
    }

    public static function can(string $permission): bool
    {
        return AdminPermission::can(self::role(),$permission);
    }

    public static function requirePermission(string $permission): void
    {
        self::requireLogin();
        if(self::can($permission)) return;
        HttpError::render(403,'Bu işlem için yönetim yetkin bulunmuyor.');
        exit;
    }

    public static function login(array $admin): void
    {
        session_regenerate_id(true);
        unset($_SESSION['user'],$_SESSION['_auth_checked_at'],$_SESSION['_activity_touched_at']);
        $_SESSION['admin']=[
            'id'=>(int)$admin['id'],
            'name'=>(string)$admin['display_name'],
            'username'=>(string)$admin['username'],
            'email'=>(string)$admin['email'],
            'role'=>(string)($admin['role']??'writer'),
        ];
        $_SESSION['_admin_auth_checked_at']=time();
    }

    public static function logout(): void
    {
        unset($_SESSION['admin'],$_SESSION['_admin_auth_checked_at']);
        session_regenerate_id(true);
    }

    public static function requireLogin(): void
    {
        if(self::id()!==null) return;
        $_SESSION['flash_error']='Yönetim paneli için yönetici girişi yapmalısın.';
        header('Location: /admin/giris');
        exit;
    }
}
