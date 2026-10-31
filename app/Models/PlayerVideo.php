<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;use PDO;use RuntimeException;
final class PlayerVideo
{
    public static function validateUrl(string $url): ?string
    {
        $url=trim($url);if(!filter_var($url,FILTER_VALIDATE_URL))return'Geçerli bir video bağlantısı gir.';$p=parse_url($url);if(($p['scheme']??'')!=='https')return'Video bağlantısı HTTPS olmalı.';$host=strtolower((string)($p['host']??''));
        foreach(['youtube.com','www.youtube.com','youtu.be','instagram.com','www.instagram.com','tiktok.com','www.tiktok.com'] as $allowed)if($host===$allowed)return null;return'Yalnızca YouTube, Instagram veya TikTok bağlantıları desteklenir.';
    }
    public static function provider(string $url): string { $h=strtolower((string)(parse_url($url,PHP_URL_HOST)??''));return str_contains($h,'youtu')?'youtube':(str_contains($h,'instagram')?'instagram':'tiktok'); }
    public static function add(int $userId,string $title,string $url): int
    {
        $title=trim($title);$url=trim($url);if(strlen($title)<2||strlen($title)>120)throw new RuntimeException('Video başlığı 2-120 karakter olmalı.');if(($e=self::validateUrl($url))!==null)throw new RuntimeException($e);
        $pdo=Database::connection();$count=$pdo->prepare('SELECT COUNT(*) FROM player_videos WHERE user_id=:u AND status="active"');$count->execute(['u'=>$userId]);if((int)$count->fetchColumn()>=6)throw new RuntimeException('Vitrinde en fazla 6 aktif video olabilir.');
        $s=$pdo->prepare('INSERT INTO player_videos (user_id,title,url,provider,status) VALUES (:u,:t,:url,:p,"active")');$s->execute(['u'=>$userId,'t'=>$title,'url'=>$url,'p'=>self::provider($url)]);return(int)$pdo->lastInsertId();
    }
    public static function forUser(int $userId): array { $s=Database::connection()->prepare('SELECT id,title,url,provider,created_at FROM player_videos WHERE user_id=:u AND status="active" ORDER BY created_at DESC LIMIT 6');$s->execute(['u'=>$userId]);return$s->fetchAll(PDO::FETCH_ASSOC)?:[]; }
    public static function deleteOwned(int $id,int $userId): bool { $s=Database::connection()->prepare('DELETE FROM player_videos WHERE id=:id AND user_id=:u');$s->execute(['id'=>$id,'u'=>$userId]);return$s->rowCount()>0; }
}
