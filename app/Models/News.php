<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\AdminPermission;
use PDO;

final class News
{
    public const CATEGORIES=['TakımBul','Amatör Futbol','Transfer','Halı Saha','Duyurular'];
    public const STATUSES=['draft','published'];

    public static function validateInput(array $data): array
    {
        $errors=[];
        $title=trim((string)($data['title']??''));$summary=trim((string)($data['summary']??''));$body=trim((string)($data['body']??''));$category=trim((string)($data['category']??''));$cover=trim((string)($data['cover_image_url']??''));$seoTitle=trim((string)($data['seo_title']??''));$seoDescription=trim((string)($data['seo_description']??''));$status=trim((string)($data['status']??'draft'));
        if(self::length($title)<5||self::length($title)>180)$errors['title']='Başlık 5–180 karakter arasında olmalı.';
        if(self::length($summary)>500)$errors['summary']='Özet 500 karakteri geçemez.';
        if(self::length($body)<20)$errors['body']='Haber içeriği en az 20 karakter olmalı.';
        if(!in_array($category,self::CATEGORIES,true))$errors['category']='Haber kategorisi geçersiz.';
        if(!in_array($status,self::STATUSES,true))$errors['status']='Haber durumu geçersiz.';
        if($cover!==''&&!self::isSafeUrlOrLocal($cover))$errors['cover_image_url']='Kapak görseli / ile başlayan site içi yol veya http/https adresi olmalı.';
        if(self::length($seoTitle)>180)$errors['seo_title']='SEO başlığı 180 karakteri geçemez.';
        if(self::length($seoDescription)>300)$errors['seo_description']='SEO açıklaması 300 karakteri geçemez.';
        $ctaText=trim((string)($data['sponsor_cta_text']??''));$ctaUrl=trim((string)($data['sponsor_cta_url']??''));
        if(self::length($ctaText)>80)$errors['sponsor_cta_text']='Sponsor CTA metni 80 karakteri geçemez.';
        if($ctaUrl!==''&&!self::isSafeExternalUrl($ctaUrl))$errors['sponsor_cta_url']='Sponsor CTA yalnız http/https adresi olabilir.';
        if(($ctaText===''&&$ctaUrl!=='')||($ctaText!==''&&$ctaUrl===''))$errors['sponsor_cta_text']='Sponsor CTA için metin ve bağlantıyı birlikte gir.';
        return $errors;
    }

    public static function published(int $limit=12): array
    {
        $limit=max(1,min(50,$limit));
        $sql=self::publicSelect().' WHERE n.status="published" AND n.published_at IS NOT NULL AND n.published_at<=NOW() ORDER BY n.is_featured DESC,n.published_at DESC,n.id DESC LIMIT '.$limit;
        return Database::connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    public static function featuredOrLatest(int $limit=3): array { return self::published($limit); }

    public static function findPublishedBySlug(string $slug): ?array
    {
        $stmt=Database::connection()->prepare(self::publicSelect().' WHERE n.slug=:slug AND n.status="published" AND n.published_at IS NOT NULL AND n.published_at<=NOW() LIMIT 1');
        $stmt->execute(['slug'=>trim($slug)]);$row=$stmt->fetch(PDO::FETCH_ASSOC);return is_array($row)?$row:null;
    }

    public static function incrementView(int $id): void
    {
        $stmt=Database::connection()->prepare('UPDATE news SET view_count=view_count+1 WHERE id=:id AND status="published"');$stmt->execute(['id'=>$id]);
    }

    public static function allForAdmin(?int $adminId=null,bool $ownOnly=false): array
    {
        $sql='SELECT n.*,a.display_name author_name,a.username author_username,a.public_title author_title,a.public_slug author_slug,a.public_profile_enabled author_public_profile FROM news n LEFT JOIN admins a ON a.id=n.created_by_admin_id';$params=[];
        if($ownOnly&&$adminId!==null){$sql.=' WHERE n.created_by_admin_id=:admin';$params['admin']=$adminId;}
        $sql.=' ORDER BY FIELD(n.status,"published","draft"),COALESCE(n.published_at,n.created_at) DESC,n.id DESC';
        $stmt=Database::connection()->prepare($sql);$stmt->execute($params);return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    public static function find(int $id): ?array
    {
        $stmt=Database::connection()->prepare('SELECT n.*,a.display_name author_name,a.username author_username,a.public_title author_title,a.public_slug author_slug,a.public_profile_enabled author_public_profile FROM news n LEFT JOIN admins a ON a.id=n.created_by_admin_id WHERE n.id=:id LIMIT 1');
        $stmt->execute(['id'=>$id]);$row=$stmt->fetch(PDO::FETCH_ASSOC);return is_array($row)?$row:null;
    }

    public static function canAdminMutate(array $news,int $adminId,string $role): bool
    {
        if(AdminPermission::can($role,'news.edit_any'))return true;
        return AdminPermission::can($role,'news.edit_own')&&(int)($news['created_by_admin_id']??0)===$adminId;
    }

    /** @return array<int,array<string,mixed>> */
    public static function publishedByAuthor(int $adminId,int $limit=30): array
    {
        $limit=max(1,min(100,$limit));$stmt=Database::connection()->prepare(self::publicSelect().' WHERE n.status="published" AND n.published_at<=NOW() AND n.created_by_admin_id=:admin ORDER BY n.published_at DESC,n.id DESC LIMIT '.$limit);$stmt->execute(['admin'=>$adminId]);return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    public static function create(array $data,int $adminId): int
    {
        $pdo=Database::connection();$slug=self::uniqueSlug(self::slugify((string)($data['title']??'')));$payload=self::payload($data);$payload['slug']=$slug;$payload['created_by_admin_id']=$adminId;$payload['updated_by_admin_id']=$adminId;
        $stmt=$pdo->prepare('INSERT INTO news (title,slug,summary,body,category,cover_image_url,seo_title,seo_description,status,is_featured,published_at,created_by_admin_id,updated_by_admin_id,sponsor_id,sponsor_cta_text,sponsor_cta_url) VALUES (:title,:slug,:summary,:body,:category,:cover_image_url,:seo_title,:seo_description,:status,:is_featured,:published_at,:created_by_admin_id,:updated_by_admin_id,:sponsor_id,:sponsor_cta_text,:sponsor_cta_url)');
        $stmt->execute($payload);return (int)$pdo->lastInsertId();
    }

    public static function update(int $id,array $data,int $adminId): bool
    {
        $current=self::find($id);if(!$current)return false;$payload=self::payload($data);$newBase=self::slugify((string)($data['title']??''));$currentBase=preg_replace('/-\d+$/','',(string)$current['slug'])?:(string)$current['slug'];$payload['slug']=$newBase===$currentBase?(string)$current['slug']:self::uniqueSlug($newBase,$id);
        if($payload['status']==='published')$payload['published_at']=!empty($current['published_at'])?(string)$current['published_at']:date('Y-m-d H:i:s');if($payload['status']==='draft')$payload['published_at']=null;$payload['id']=$id;$payload['updated_by_admin_id']=$adminId;
        $stmt=Database::connection()->prepare('UPDATE news SET title=:title,slug=:slug,summary=:summary,body=:body,category=:category,cover_image_url=:cover_image_url,seo_title=:seo_title,seo_description=:seo_description,status=:status,is_featured=:is_featured,published_at=:published_at,updated_by_admin_id=:updated_by_admin_id,sponsor_id=:sponsor_id,sponsor_cta_text=:sponsor_cta_text,sponsor_cta_url=:sponsor_cta_url,updated_at=NOW() WHERE id=:id');$stmt->execute($payload);return $stmt->rowCount()>=0;
    }

    public static function setStatus(int $id,string $status,int $adminId): bool
    {
        if(!in_array($status,self::STATUSES,true))return false;
        $sql=$status==='published'?'UPDATE news SET status="published",published_at=COALESCE(published_at,NOW()),updated_by_admin_id=:admin,updated_at=NOW() WHERE id=:id':'UPDATE news SET status="draft",published_at=NULL,updated_by_admin_id=:admin,updated_at=NOW() WHERE id=:id';
        $stmt=Database::connection()->prepare($sql);$stmt->execute(['admin'=>$adminId,'id'=>$id]);return $stmt->rowCount()===1;
    }

    public static function delete(int $id): bool { $stmt=Database::connection()->prepare('DELETE FROM news WHERE id=:id');$stmt->execute(['id'=>$id]);return $stmt->rowCount()===1; }

    public static function safeImageUrl(?string $value): ?string { $value=trim((string)$value);return $value!==''&&self::isSafeUrlOrLocal($value)?$value:null; }
    public static function slugify(string $text): string { $text=strtr(trim($text),['İ'=>'I','ı'=>'i','Ş'=>'S','ş'=>'s','Ğ'=>'G','ğ'=>'g','Ü'=>'U','ü'=>'u','Ö'=>'O','ö'=>'o','Ç'=>'C','ç'=>'c']);$text=strtolower($text);$text=preg_replace('/[^a-z0-9]+/','-',$text)??'';$text=trim($text,'-');return $text!==''?substr($text,0,170):'haber'; }
    public static function isSafeExternalUrl(string $value): bool { $parts=parse_url(trim($value));return is_array($parts)&&in_array(strtolower((string)($parts['scheme']??'')),['http','https'],true)&&trim((string)($parts['host']??''))!==''; }

    private static function publicSelect(): string
    {
        return 'SELECT n.*,a.display_name author_name,a.public_title author_title,a.public_slug author_slug,a.public_profile_enabled author_public_profile,a.avatar_path author_avatar,s.name sponsor_name,s.slug sponsor_slug,s.logo_url sponsor_logo_url,s.website_url sponsor_website_url,s.description sponsor_description,s.status sponsor_status FROM news n LEFT JOIN admins a ON a.id=n.created_by_admin_id LEFT JOIN news_sponsors s ON s.id=n.sponsor_id';
    }

    private static function payload(array $data): array
    {
        $status=in_array((string)($data['status']??''),self::STATUSES,true)?(string)$data['status']:'draft';$sponsorId=(int)($data['sponsor_id']??0);
        return ['title'=>trim((string)($data['title']??'')),'summary'=>self::nullIfEmpty((string)($data['summary']??'')),'body'=>trim((string)($data['body']??'')),'category'=>in_array((string)($data['category']??''),self::CATEGORIES,true)?(string)$data['category']:'TakımBul','cover_image_url'=>self::safeImageUrl((string)($data['cover_image_url']??'')),'seo_title'=>self::nullIfEmpty((string)($data['seo_title']??'')),'seo_description'=>self::nullIfEmpty((string)($data['seo_description']??'')),'status'=>$status,'is_featured'=>!empty($data['is_featured'])?1:0,'published_at'=>$status==='published'?date('Y-m-d H:i:s'):null,'sponsor_id'=>$sponsorId>0?$sponsorId:null,'sponsor_cta_text'=>self::nullIfEmpty((string)($data['sponsor_cta_text']??'')),'sponsor_cta_url'=>self::nullIfEmpty((string)($data['sponsor_cta_url']??''))];
    }

    private static function uniqueSlug(string $base,?int $ignoreId=null): string { $pdo=Database::connection();$slug=$base;$suffix=2;while(true){$sql='SELECT id FROM news WHERE slug=:slug'.($ignoreId!==null?' AND id<>:id':'').' LIMIT 1';$stmt=$pdo->prepare($sql);$params=['slug'=>$slug];if($ignoreId!==null)$params['id']=$ignoreId;$stmt->execute($params);if(!$stmt->fetchColumn())return $slug;$slug=substr($base,0,160).'-'.$suffix++;} }
    private static function isSafeUrlOrLocal(string $value): bool { if(str_starts_with($value,'/'))return !str_starts_with($value,'//');return self::isSafeExternalUrl($value); }
    private static function nullIfEmpty(string $value): ?string { $value=trim($value);return $value===''?null:$value; }
    private static function length(string $value): int { return function_exists('mb_strlen')?mb_strlen($value,'UTF-8'):strlen($value); }
}
