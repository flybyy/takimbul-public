<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\TurkeyLocation;
use PDO;
use Throwable;

final class Team
{
    public static function validateInput(array $data): array
    {
        $errors = [];
        $name = trim((string) ($data['name'] ?? ''));
        $province = trim((string) ($data['province'] ?? ''));
        $district = trim((string) ($data['district'] ?? ''));
        $year = trim((string) ($data['founded_year'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));

        if (strlen($name) < 2 || strlen($name) > 120) {
            $errors['name'] = 'Takım adı 2-120 karakter olmalı.';
        }
        if ($province === '' || TurkeyLocation::canonicalProvince($province) === null) {
            $errors['province'] = 'Geçerli bir il seç.';
        }
        if ($district === '') {
            $errors['district'] = 'Geçerli bir ilçe seç.';
        } elseif ($province !== '' && TurkeyLocation::canonicalProvince($province) !== null && !TurkeyLocation::isValidPair($province,$district)) {
            $errors['district'] = 'Geçerli bir ilçe seç.';
        }
        if ($year !== '' && (!ctype_digit($year) || (int) $year < 1900 || (int) $year > (int) date('Y'))) {
            $errors['founded_year'] = 'Kuruluş yılı geçersiz.';
        }
        if (strlen($description) > 700) {
            $errors['description'] = 'Açıklama 700 karakteri geçemez.';
        }

        return $errors;
    }

    public static function validateClubSettings(array $data): array
    {
        $errors=[];
        $short=trim((string)($data['short_name']??''));$primary=trim((string)($data['primary_color']??''));$secondary=trim((string)($data['secondary_color']??''));
        $venue=trim((string)($data['home_venue']??''));$instagram=trim((string)($data['instagram_url']??''));
        if(strlen($short)>24)$errors['short_name']='Kısa takım adı 24 karakteri geçemez.';
        foreach(['primary_color'=>$primary,'secondary_color'=>$secondary] as $key=>$color){if($color!==''&&!preg_match('/^#[0-9A-Fa-f]{6}$/',$color))$errors[$key]='Renk #RRGGBB formatında olmalı.';}
        if(strlen($venue)>160)$errors['home_venue']='Saha adı 160 karakteri geçemez.';
        if($instagram!==''){
            $valid=filter_var($instagram,FILTER_VALIDATE_URL)!==false;$host=$valid?strtolower((string)parse_url($instagram,PHP_URL_HOST)):'';
            if(!$valid||!in_array($host,['instagram.com','www.instagram.com'],true)||!str_starts_with(strtolower($instagram),'https://'))$errors['instagram_url']='Geçerli bir https://instagram.com adresi gir.';
        }
        return $errors;
    }

    public static function updateClubSettings(int $teamId,int $captainId,array $data): void
    {
        if(!self::isCaptain($teamId,$captainId))throw new \RuntimeException('Kulüp bilgilerini yalnızca takım kaptanı değiştirebilir.');
        $errors=self::validateClubSettings($data);if($errors!==[])throw new \RuntimeException((string)reset($errors));
        $stmt=Database::connection()->prepare('UPDATE teams SET short_name=:short_name,primary_color=:primary_color,secondary_color=:secondary_color,home_venue=:home_venue,instagram_url=:instagram_url WHERE id=:id AND captain_user_id=:captain_id AND status="active"');
        $stmt->execute([
            'short_name'=>trim((string)($data['short_name']??''))?:null,'primary_color'=>trim((string)($data['primary_color']??''))?:null,'secondary_color'=>trim((string)($data['secondary_color']??''))?:null,
            'home_venue'=>trim((string)($data['home_venue']??''))?:null,'instagram_url'=>trim((string)($data['instagram_url']??''))?:null,'id'=>$teamId,'captain_id'=>$captainId,
        ]);
    }

    public static function slugify(string $value): string
    {
        $map = [
            'Ç'=>'C','Ğ'=>'G','İ'=>'I','Ö'=>'O','Ş'=>'S','Ü'=>'U',
            'ç'=>'c','ğ'=>'g','ı'=>'i','i'=>'i','ö'=>'o','ş'=>'s','ü'=>'u',
        ];
        $value = strtr(trim($value), $map);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }

    public static function create(int $captainId, array $data): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $slugBase = self::slugify((string) $data['name']);
            $slug = $slugBase . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
            $statement = $pdo->prepare(
                'INSERT INTO teams (name, slug, province, district, founded_year, description, captain_user_id)
                 VALUES (:name, :slug, :province, :district, :founded_year, :description, :captain_user_id)'
            );
            $statement->execute([
                'name' => trim((string) $data['name']),
                'slug' => $slug,
                'province' => TurkeyLocation::canonicalProvince((string)$data['province']) ?? trim((string)$data['province']),
                'district' => TurkeyLocation::canonicalDistrict((string)$data['province'],(string)$data['district']) ?? trim((string)$data['district']),
                'founded_year' => ($data['founded_year'] ?? '') !== '' ? (int) $data['founded_year'] : null,
                'description' => trim((string) ($data['description'] ?? '')) ?: null,
                'captain_user_id' => $captainId,
            ]);
            $teamId = (int) $pdo->lastInsertId();

            $member = $pdo->prepare(
                'INSERT INTO team_members (team_id, user_id, role, status) VALUES (:team_id, :user_id, "captain", "active")'
            );
            $member->execute(['team_id' => $teamId, 'user_id' => $captainId]);
            $pdo->commit();
            return $teamId;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT t.*, u.display_name AS captain_name
             FROM teams t INNER JOIN users u ON u.id = t.captain_user_id
             WHERE t.id = :id AND t.status = "active" LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $team = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($team)) {
            return null;
        }

        $roster = Database::connection()->prepare(
            'SELECT u.id, u.display_name, u.profile_verified_at, tm.role, tm.jersey_number, tm.team_position, tm.squad_status, tm.joined_at, p.primary_position, p.avatar_path
             FROM team_members tm
             INNER JOIN users u ON u.id = tm.user_id
             LEFT JOIN player_profiles p ON p.user_id = u.id
             WHERE tm.team_id = :team_id AND tm.status = "active"
             ORDER BY FIELD(tm.role, "captain", "vice_captain", "player"), u.display_name'
        );
        $roster->execute(['team_id' => $id]);
        $team['roster'] = $roster->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $team;
    }

    public static function activeMembershipsForUser(int $userId): array
    {
        $stmt=Database::connection()->prepare(
            'SELECT t.id,t.name,t.logo_path,t.province,t.district,t.short_name,tm.role,tm.jersey_number,tm.team_position,tm.squad_status,tm.joined_at
             FROM team_members tm INNER JOIN teams t ON t.id=tm.team_id
             WHERE tm.user_id=:user_id AND tm.status="active" AND t.status="active"
             ORDER BY tm.joined_at DESC,t.name ASC'
        );
        $stmt->execute(['user_id'=>$userId]);return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    public static function forUser(int $userId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT t.*, tm.role FROM team_members tm INNER JOIN teams t ON t.id = tm.team_id
             WHERE tm.user_id = :user_id AND tm.status = "active" AND t.status = "active"
             ORDER BY t.created_at DESC'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function captainTeamsForUser(int $userId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, province, district FROM teams WHERE captain_user_id = :user_id AND status = "active" ORDER BY created_at DESC'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }


    public static function managedTeamsForUser(int $userId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT t.id,t.name,t.province,t.district,tm.role FROM team_members tm INNER JOIN teams t ON t.id=tm.team_id
             WHERE tm.user_id=:user_id AND tm.status="active" AND tm.role IN ("captain","vice_captain") AND t.status="active" ORDER BY t.created_at DESC'
        );
        $statement->execute(['user_id'=>$userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function canManageSporting(int $teamId, int $userId): bool
    {
        $role = TeamMembership::roleForUser($teamId,$userId);
        return $role !== null && TeamMembership::canManageSportingRole($role);
    }

    public static function isCaptain(int $teamId, int $userId): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT 1 FROM teams WHERE id = :team_id AND captain_user_id = :user_id AND status = "active" LIMIT 1'
        );
        $statement->execute(['team_id' => $teamId, 'user_id' => $userId]);
        return (bool) $statement->fetchColumn();
    }

    public static function recent(int $limit = 18): array
    {
        $sql = 'SELECT id, name, province, district, founded_year, description FROM teams WHERE status = "active" ORDER BY created_at DESC LIMIT ' . max(1, min(100, $limit));
        return Database::connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
