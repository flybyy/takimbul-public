<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class NewsSponsor
{
    public static function validateInput(array $data): array
    {
        $errors=[];$name=trim((string)($data['name']??''));$logo=trim((string)($data['logo_url']??''));$website=trim((string)($data['website_url']??''));$description=trim((string)($data['description']??''));
        if(self::length($name)<2||self::length($name)>120)$errors['name']='Sponsor adı 2–120 karakter arasında olmalı.';
        if($logo!==''&&!self::isSafeReference($logo))$errors['logo_url']='Logo yalnız site içi / yolu veya http/https adresi olabilir.';
        if($website!==''&&!News::isSafeExternalUrl($website))$errors['website_url']='Web sitesi yalnız http/https adresi olabilir.';
        if(self::length($description)>500)$errors['description']='Açıklama 500 karakteri geçemez.';
        return $errors;
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(): array { return Database::connection()->query('SELECT * FROM news_sponsors ORDER BY FIELD(status,"active","inactive"),name')->fetchAll(PDO::FETCH_ASSOC)?:[]; }
    /** @return array<int,array<string,mixed>> */
    public static function activeForSelection(int $includeId=0): array
    {
        if($includeId>0){$stmt=Database::connection()->prepare('SELECT * FROM news_sponsors WHERE status="active" OR id=:id ORDER BY FIELD(status,"active","inactive"),name');$stmt->execute(['id'=>$includeId]);return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];}
        return Database::connection()->query('SELECT * FROM news_sponsors WHERE status="active" ORDER BY name')->fetchAll(PDO::FETCH_ASSOC)?:[];
    }
    public static function find(int $id): ?array { $stmt=Database::connection()->prepare('SELECT * FROM news_sponsors WHERE id=:id LIMIT 1');$stmt->execute(['id'=>$id]);$row=$stmt->fetch(PDO::FETCH_ASSOC);return is_array($row)?$row:null; }
    public static function isSelectable(int $id,int $existingSponsorId=0): bool { $s=self::find($id);return (bool)$s&&(($s['status']??'')==='active'||$id===$existingSponsorId); }

    public static function create(array $data,int $adminId): int
    {
        $pdo=Database::connection();$slug=self::uniqueSlug(self::slugify((string)$data['name']));$stmt=$pdo->prepare('INSERT INTO news_sponsors (name,slug,logo_url,website_url,description,status,created_by_admin_id,updated_by_admin_id) VALUES (:name,:slug,:logo,:website,:description,"active",:created_by,:updated_by)');$stmt->execute(['name'=>trim((string)$data['name']),'slug'=>$slug,'logo'=>self::safeNullable((string)($data['logo_url']??''),true),'website'=>self::safeNullable((string)($data['website_url']??''),false),'description'=>self::nullIfEmpty((string)($data['description']??'')),'created_by'=>$adminId,'updated_by'=>$adminId]);return (int)$pdo->lastInsertId();
    }
    public static function update(int $id,array $data,int $adminId): bool
    {
        $current=self::find($id);if(!$current)return false;$base=self::slugify((string)$data['name']);$slug=$base===(string)$current['slug']?(string)$current['slug']:self::uniqueSlug($base,$id);$stmt=Database::connection()->prepare('UPDATE news_sponsors SET name=:name,slug=:slug,logo_url=:logo,website_url=:website,description=:description,updated_by_admin_id=:updated_by,updated_at=NOW() WHERE id=:id');$stmt->execute(['name'=>trim((string)$data['name']),'slug'=>$slug,'logo'=>self::safeNullable((string)($data['logo_url']??''),true),'website'=>self::safeNullable((string)($data['website_url']??''),false),'description'=>self::nullIfEmpty((string)($data['description']??'')),'updated_by'=>$adminId,'id'=>$id]);return $stmt->rowCount()>=0;
    }
    public static function setStatus(int $id,string $status,int $adminId): bool { if(!in_array($status,['active','inactive'],true))return false;$stmt=Database::connection()->prepare('UPDATE news_sponsors SET status=:status,updated_by_admin_id=:admin,updated_at=NOW() WHERE id=:id');$stmt->execute(['status'=>$status,'admin'=>$adminId,'id'=>$id]);return $stmt->rowCount()===1; }

    public static function slugify(string $value): string { return News::slugify($value); }
    private static function uniqueSlug(string $base,?int $ignore=null): string { $slug=$base;$n=2;$pdo=Database::connection();while(true){$sql='SELECT id FROM news_sponsors WHERE slug=:slug'.($ignore?' AND id<>:id':'').' LIMIT 1';$stmt=$pdo->prepare($sql);$p=['slug'=>$slug];if($ignore)$p['id']=$ignore;$stmt->execute($p);if(!$stmt->fetchColumn())return $slug;$slug=substr($base,0,140).'-'.$n++;} }
    private static function isSafeReference(string $value): bool { return str_starts_with($value,'/')?!str_starts_with($value,'//'):News::isSafeExternalUrl($value); }
    private static function safeNullable(string $value,bool $allowLocal): ?string { $value=trim($value);if($value==='')return null;if($allowLocal&&str_starts_with($value,'/')&&!str_starts_with($value,'//'))return $value;return News::isSafeExternalUrl($value)?$value:null; }
    private static function nullIfEmpty(string $v): ?string { $v=trim($v);return $v===''?null:$v; }
    private static function length(string $v): int { return function_exists('mb_strlen')?mb_strlen($v,'UTF-8'):strlen($v); }
}
