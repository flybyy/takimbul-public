<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class PlayerProfile
{
    public const POSITIONS = ['Kaleci', 'Defans', 'Orta Saha', 'Kanat', 'Forvet'];
    public const FEET = ['Sağ', 'Sol', 'İki'];
    public const VISIBILITIES = ['public', 'members', 'private'];

    public static function validateInput(array $data): array
    {
        $errors = [];
        $primary = trim((string) ($data['primary_position'] ?? ''));
        $secondary = trim((string) ($data['secondary_position'] ?? ''));
        $foot = trim((string) ($data['dominant_foot'] ?? ''));
        $visibility = trim((string) ($data['visibility'] ?? 'public'));
        $birthYear = trim((string) ($data['birth_year'] ?? ''));
        $height = trim((string) ($data['height_cm'] ?? ''));
        $bio = trim((string) ($data['bio'] ?? ''));

        if ($primary !== '' && !in_array($primary, self::POSITIONS, true)) {
            $errors['primary_position'] = 'Geçersiz mevki.';
        }
        if ($secondary !== '' && !in_array($secondary, self::POSITIONS, true)) {
            $errors['secondary_position'] = 'Geçersiz alternatif mevki.';
        }
        if ($foot !== '' && !in_array($foot, self::FEET, true)) {
            $errors['dominant_foot'] = 'Geçersiz dominant ayak seçimi.';
        }
        if (!in_array($visibility, self::VISIBILITIES, true)) {
            $errors['visibility'] = 'Geçersiz profil görünürlüğü.';
        }
        $currentYear = (int) date('Y');
        if ($birthYear !== '' && (!ctype_digit($birthYear) || (int) $birthYear < 1940 || (int) $birthYear > $currentYear - 8)) {
            $errors['birth_year'] = 'Doğum yılı geçersiz.';
        }
        if ($height !== '' && (!ctype_digit($height) || (int) $height < 120 || (int) $height > 230)) {
            $errors['height_cm'] = 'Boy bilgisi 120-230 cm aralığında olmalı.';
        }
        if (strlen($bio) > 500) {
            $errors['bio'] = 'Futbol özeti 500 karakteri geçemez.';
        }

        return $errors;
    }

    public static function upsert(int $userId, array $data): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO player_profiles (user_id, primary_position, secondary_position, dominant_foot, birth_year, show_age, height_cm, bio, avatar_path, visibility)
             VALUES (:user_id, :primary_position, :secondary_position, :dominant_foot, :birth_year, :show_age, :height_cm, :bio, :avatar_path, :visibility)
             ON DUPLICATE KEY UPDATE primary_position = VALUES(primary_position), secondary_position = VALUES(secondary_position), dominant_foot = VALUES(dominant_foot), birth_year = VALUES(birth_year), show_age = VALUES(show_age), height_cm = VALUES(height_cm), bio = VALUES(bio), avatar_path = VALUES(avatar_path), visibility = VALUES(visibility)'
        );
        $statement->execute([
            'user_id' => $userId,
            'primary_position' => self::nullIfEmpty($data['primary_position'] ?? null),
            'secondary_position' => self::nullIfEmpty($data['secondary_position'] ?? null),
            'dominant_foot' => self::nullIfEmpty($data['dominant_foot'] ?? null),
            'birth_year' => self::nullIfEmpty($data['birth_year'] ?? null),
            'show_age' => !empty($data['show_age']) ? 1 : 0,
            'height_cm' => self::nullIfEmpty($data['height_cm'] ?? null),
            'bio' => self::nullIfEmpty($data['bio'] ?? null),
            'avatar_path' => self::nullIfEmpty($data['avatar_path'] ?? null),
            'visibility' => (string) ($data['visibility'] ?? 'public'),
        ]);
    }

    public static function validateAvatarFile(array $file): ?string
    {
        $error=(int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error===UPLOAD_ERR_NO_FILE) return null;
        if ($error!==UPLOAD_ERR_OK) return 'Profil fotoğrafı yüklenemedi.';
        $size=(int)($file['size'] ?? 0);
        if ($size<1 || $size>3*1024*1024) return 'Profil fotoğrafı en fazla 3 MB olabilir.';
        $name=(string)($file['name'] ?? '');
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        if (!in_array($ext,['jpg','jpeg','png','webp'],true)) return 'Profil fotoğrafı JPEG, PNG veya WebP olmalı.';
        $tmp=(string)($file['tmp_name'] ?? '');
        if ($tmp==='' || !is_file($tmp)) return 'Profil fotoğrafı dosyası bulunamadı.';
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        if (!in_array($mime,['image/jpeg','image/png','image/webp'],true)) return 'Profil fotoğrafının dosya türü geçersiz.';
        $info=@getimagesize($tmp);
        if (!is_array($info) || ($info[0]??0)<1 || ($info[1]??0)<1) return 'Profil fotoğrafı okunamadı.';
        if (($info[0]??0)>6000 || ($info[1]??0)>6000) return 'Profil fotoğrafı çözünürlüğü çok yüksek.';
        return null;
    }

    public static function storeAvatar(int $userId, array $file, ?string $oldPath=null): ?string
    {
        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return $oldPath;
        if (($error=self::validateAvatarFile($file))!==null) throw new \RuntimeException($error);
        $tmp=(string)$file['tmp_name'];
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        $publicRoot=dirname(__DIR__,2).'/public';
        $dir=$publicRoot.'/uploads/avatars';
        if (!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) throw new \RuntimeException('Avatar klasörü oluşturulamadı.');
        $base='u'.$userId.'-'.bin2hex(random_bytes(8));
        $targetExt=$mime==='image/png'?'png':($mime==='image/webp'?'webp':'jpg');
        if($targetExt==='webp' && function_exists('imagecreatefromstring') && !function_exists('imagewebp')) $targetExt='jpg';
        $relative='/uploads/avatars/'.$base.'.'.$targetExt;
        $target=$publicRoot.$relative;

        $stored=false;
        if (function_exists('imagecreatefromstring')) {
            $bytes=file_get_contents($tmp);
            $img=$bytes!==false?@imagecreatefromstring($bytes):false;
            if ($img!==false) {
                $width=imagesx($img); $height=imagesy($img); $max=1200;
                if ($width>$max || $height>$max) {
                    $scale=min($max/$width,$max/$height);$newW=max(1,(int)round($width*$scale));$newH=max(1,(int)round($height*$scale));
                    $canvas=imagecreatetruecolor($newW,$newH);
                    if ($targetExt==='png') { imagealphablending($canvas,false); imagesavealpha($canvas,true); }
                    imagecopyresampled($canvas,$img,0,0,0,0,$newW,$newH,$width,$height); imagedestroy($img); $img=$canvas;
                }
                if ($targetExt==='png') $stored=imagepng($img,$target,7);
                elseif ($targetExt==='webp' && function_exists('imagewebp')) $stored=imagewebp($img,$target,82);
                else $stored=imagejpeg($img,$target,86);
                imagedestroy($img);
            }
        }
        if (!$stored) {
            if (is_uploaded_file($tmp)) $stored=move_uploaded_file($tmp,$target);
            else $stored=copy($tmp,$target);
        }
        if (!$stored) throw new \RuntimeException('Profil fotoğrafı kaydedilemedi.');
        @chmod($target,0644);
        return $relative;
    }

    public static function deleteAvatar(?string $path): void
    {
        $path=trim((string)$path);
        if ($path==='' || !str_starts_with($path,'/uploads/avatars/')) return;
        $full=dirname(__DIR__,2).'/public'.$path;
        if (is_file($full)) @unlink($full);
    }

    public static function find(int $userId): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM player_profiles WHERE user_id = :user_id LIMIT 1');
        $statement->execute(['user_id' => $userId]);
        $profile = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($profile) ? $profile : null;
    }

    private static function nullIfEmpty(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }
}
