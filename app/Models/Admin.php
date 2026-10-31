<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Admin
{
    private const RESERVED_IDENTIFIERS=['veribayt','admin@takimbul.com.tr'];
    public static function findByIdentifier(string $identifier): ?array
    {
        $identifier=trim($identifier);
        if($identifier==='') return null;
        $stmt=Database::connection()->prepare('SELECT id,username,email,password_hash,display_name,role,public_slug,public_title,public_bio,avatar_path,instagram_url,x_url,public_profile_enabled,status,last_login_at FROM admins WHERE LOWER(username)=LOWER(:identifier_username) OR LOWER(email)=LOWER(:identifier_email) LIMIT 1');
        $stmt->execute(['identifier_username'=>$identifier,'identifier_email'=>$identifier]);
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row)?$row:null;
    }

    public static function identifierReserved(string $identifier): bool
    {
        $identifier=trim($identifier);
        if($identifier==='') return false;
        if(in_array(strtolower($identifier),self::RESERVED_IDENTIFIERS,true)) return true;
        try{
            $stmt=Database::connection()->prepare('SELECT 1 FROM admins WHERE LOWER(username)=LOWER(:identifier_username) OR LOWER(email)=LOWER(:identifier_email) LIMIT 1');
            $stmt->execute(['identifier_username'=>$identifier,'identifier_email'=>$identifier]);
            return (bool)$stmt->fetchColumn();
        }catch(\Throwable){return false;}
    }

    public static function find(int $id): ?array
    {
        $stmt=Database::connection()->prepare('SELECT id,username,email,display_name,role,public_slug,public_title,public_bio,avatar_path,instagram_url,x_url,public_profile_enabled,status,last_login_at FROM admins WHERE id=:id LIMIT 1');
        $stmt->execute(['id'=>$id]);
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row)?$row:null;
    }

    public static function findPublicBySlug(string $slug): ?array
    {
        $stmt=Database::connection()->prepare('SELECT id,display_name,public_slug,public_title,public_bio,avatar_path,instagram_url,x_url,public_profile_enabled FROM admins WHERE public_slug=:slug AND status="active" AND public_profile_enabled=1 LIMIT 1');
        $stmt->execute(['slug'=>trim($slug)]);$row=$stmt->fetch(PDO::FETCH_ASSOC);return is_array($row)?$row:null;
    }

    /** @return array<int,array<string,mixed>> */
    public static function publicAuthors(): array
    {
        return Database::connection()->query('SELECT id,display_name,public_slug,public_title,public_bio,avatar_path,instagram_url,x_url FROM admins WHERE status="active" AND public_profile_enabled=1 AND public_slug IS NOT NULL AND public_slug<>"" ORDER BY display_name')->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    public static function countActiveSuperAdmins(): int
    {
        $stmt=Database::connection()->query("SELECT COUNT(*) FROM admins WHERE role='super_admin' AND status='active'");
        return (int)$stmt->fetchColumn();
    }

    public static function canChangeRoleOrStatus(int $targetId,string $newRole,string $newStatus): bool
    {
        if(!in_array($newRole,['super_admin','authorized','writer'],true)||!in_array($newStatus,['active','disabled'],true)) return false;
        $current=self::find($targetId);if(!$current)return false;
        $isActiveSuper=($current['role']??'')==='super_admin'&&($current['status']??'')==='active';
        $removesSuper=$newRole!=='super_admin'||$newStatus!=='active';
        if($isActiveSuper&&$removesSuper&&self::countActiveSuperAdmins()<=1)return false;
        return true;
    }


    /** @return array<int,array<string,mixed>> */
    public static function allStaff(): array
    {
        return Database::connection()->query('SELECT id,username,email,display_name,role,public_slug,public_title,public_profile_enabled,status,last_login_at,created_at,updated_at FROM admins ORDER BY FIELD(role,"super_admin","authorized","writer"),display_name')->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    public static function validateStaffInput(array $data,?int $ignoreId=null,bool $creating=false): array
    {
        $errors=[];$name=trim((string)($data['display_name']??''));$username=strtolower(trim((string)($data['username']??'')));$email=strtolower(trim((string)($data['email']??'')));$role=(string)($data['role']??'writer');$status=(string)($data['status']??'active');$slug=trim((string)($data['public_slug']??''));$title=trim((string)($data['public_title']??''));$bio=trim((string)($data['public_bio']??''));
        if(strlen($name)<2||strlen($name)>100)$errors['display_name']='Ad 2–100 karakter olmalı.';
        if(strlen($username)<3||strlen($username)>60||!preg_match('/^[a-z0-9._-]+$/',$username))$errors['username']='Kullanıcı adı 3–60 karakter; küçük harf, rakam, nokta, alt çizgi ve tire kullanabilir.';
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190)$errors['email']='Geçerli bir e-posta gir.';
        if(!in_array($role,['super_admin','authorized','writer'],true))$errors['role']='Rol geçersiz.';
        if(!in_array($status,['active','disabled'],true))$errors['status']='Durum geçersiz.';
        if($slug!==''&&(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$slug)||strlen($slug)>150))$errors['public_slug']='Yazar URL adı küçük harf/rakam ve tire ile yazılmalı.';
        if(strlen($title)>100)$errors['public_title']='Public unvan 100 karakteri geçemez.';
        if(strlen($bio)>500)$errors['public_bio']='Biyografi 500 karakteri geçemez.';
        foreach(['instagram_url','x_url'] as $field){$url=trim((string)($data[$field]??''));if($url!==''&&!self::safeSocialUrl($url,$field))$errors[$field]='Geçerli bir https sosyal medya adresi gir.';}
        $pdo=Database::connection();
        if(!isset($errors['username'])){$sql='SELECT id FROM admins WHERE LOWER(username)=LOWER(:value)'.($ignoreId!==null?' AND id<>:id':'').' LIMIT 1';$stmt=$pdo->prepare($sql);$params=['value'=>$username];if($ignoreId!==null)$params['id']=$ignoreId;$stmt->execute($params);if($stmt->fetchColumn())$errors['username']='Bu yönetici kullanıcı adı zaten kullanılıyor.';}
        if(!isset($errors['email'])){$sql='SELECT id FROM admins WHERE LOWER(email)=LOWER(:value)'.($ignoreId!==null?' AND id<>:id':'').' LIMIT 1';$stmt=$pdo->prepare($sql);$params=['value'=>$email];if($ignoreId!==null)$params['id']=$ignoreId;$stmt->execute($params);if($stmt->fetchColumn())$errors['email']='Bu yönetici e-postası zaten kullanılıyor.';}
        if($slug!==''&&!isset($errors['public_slug'])){$sql='SELECT id FROM admins WHERE public_slug=:value'.($ignoreId!==null?' AND id<>:id':'').' LIMIT 1';$stmt=$pdo->prepare($sql);$params=['value'=>$slug];if($ignoreId!==null)$params['id']=$ignoreId;$stmt->execute($params);if($stmt->fetchColumn())$errors['public_slug']='Bu yazar URL adı zaten kullanılıyor.';}
        if($creating){$password=(string)($data['password']??'');if(($e=User::validatePassword($password))!==null)$errors['password']=$e;}
        return $errors;
    }

    public static function createStaff(array $data): int
    {
        $pdo=Database::connection();$stmt=$pdo->prepare('INSERT INTO admins (username,email,password_hash,display_name,role,public_slug,public_title,public_bio,avatar_path,instagram_url,x_url,public_profile_enabled,status) VALUES (:username,:email,:password_hash,:display_name,:role,:public_slug,:public_title,:public_bio,:avatar_path,:instagram_url,:x_url,:public_profile_enabled,:status)');
        $stmt->execute(self::staffPayload($data,true));return (int)$pdo->lastInsertId();
    }

    public static function updateStaff(int $id,array $data): bool
    {
        $payload=self::staffPayload($data,false);$payload['id']=$id;unset($payload['password_hash']);$stmt=Database::connection()->prepare('UPDATE admins SET username=:username,email=:email,display_name=:display_name,role=:role,public_slug=:public_slug,public_title=:public_title,public_bio=:public_bio,avatar_path=:avatar_path,instagram_url=:instagram_url,x_url=:x_url,public_profile_enabled=:public_profile_enabled,status=:status,updated_at=NOW() WHERE id=:id');$stmt->execute($payload);return $stmt->rowCount()>=0;
    }

    public static function setStaffPassword(int $id,string $password): bool
    {
        if(User::validatePassword($password)!==null)return false;$stmt=Database::connection()->prepare('UPDATE admins SET password_hash=:hash,updated_at=NOW() WHERE id=:id');$stmt->execute(['hash'=>password_hash($password,PASSWORD_DEFAULT),'id'=>$id]);return $stmt->rowCount()===1;
    }

    private static function staffPayload(array $data,bool $withPassword): array
    {
        $payload=['username'=>strtolower(trim((string)($data['username']??''))),'email'=>strtolower(trim((string)($data['email']??''))),'display_name'=>trim((string)($data['display_name']??'')),'role'=>(string)($data['role']??'writer'),'public_slug'=>self::nullIfEmpty((string)($data['public_slug']??'')),'public_title'=>self::nullIfEmpty((string)($data['public_title']??'')),'public_bio'=>self::nullIfEmpty((string)($data['public_bio']??'')),'avatar_path'=>self::nullIfEmpty((string)($data['avatar_path']??'')),'instagram_url'=>self::nullIfEmpty((string)($data['instagram_url']??'')),'x_url'=>self::nullIfEmpty((string)($data['x_url']??'')),'public_profile_enabled'=>!empty($data['public_profile_enabled'])?1:0,'status'=>(string)($data['status']??'active')];if($withPassword)$payload['password_hash']=password_hash((string)($data['password']??''),PASSWORD_DEFAULT);return $payload;
    }

    private static function safeSocialUrl(string $url,string $field): bool
    {
        if(!str_starts_with(strtolower($url),'https://')||filter_var($url,FILTER_VALIDATE_URL)===false)return false;$host=strtolower((string)parse_url($url,PHP_URL_HOST));$allowed=$field==='instagram_url'?['instagram.com','www.instagram.com']:['x.com','www.x.com','twitter.com','www.twitter.com'];return in_array($host,$allowed,true);
    }
    private static function nullIfEmpty(string $v): ?string { $v=trim($v);return $v===''?null:$v; }

    public static function markLogin(int $id): void
    {
        $stmt=Database::connection()->prepare('UPDATE admins SET last_login_at=NOW(),updated_at=NOW() WHERE id=:id');
        $stmt->execute(['id'=>$id]);
    }
}
