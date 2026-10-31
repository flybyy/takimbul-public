<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\TurkeyLocation;
use PDO;

final class PlayerWantedPost
{
    public const LEVELS = ['Eğlence', 'Orta', 'Rekabetçi'];

    public static function validateInput(array $data): array
    {
        $errors = [];
        $matchAt = trim((string)($data['match_at'] ?? ''));
        if ($matchAt === '' || strtotime($matchAt) === false || strtotime($matchAt) <= time()) $errors['match_at'] = 'Maç tarihi gelecekte olmalı.';
        $province=trim((string)($data['province']??''));$district=trim((string)($data['district']??''));
        if ($province==='' || TurkeyLocation::canonicalProvince($province)===null) $errors['province']='Geçerli bir il seç.';
        elseif($district==='' || !TurkeyLocation::isValidPair($province,$district)) $errors['district']='Geçerli bir ilçe seç.';
        if (!in_array((string)($data['position'] ?? ''), PlayerProfile::POSITIONS, true)) $errors['position'] = 'Geçerli bir mevki seç.';
        $needed = (int)($data['players_needed'] ?? 0);
        if ($needed < 1 || $needed > 10) $errors['players_needed'] = 'Oyuncu sayısı 1-10 aralığında olmalı.';
        if (!in_array((string)($data['level'] ?? ''), self::LEVELS, true)) $errors['level'] = 'Geçerli bir seviye seç.';
        if (strlen(trim((string)($data['description'] ?? ''))) > 500) $errors['description'] = 'Açıklama 500 karakteri geçemez.';
        return $errors;
    }

    public static function create(int $userId, array $data): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO player_wanted_posts (team_id, created_by, match_at, province, district, venue, position, players_needed, level, fee_per_person, description) VALUES (:team_id,:created_by,:match_at,:province,:district,:venue,:position,:players_needed,:level,:fee,:description)');
        $stmt->execute([
            'team_id'=>(int)$data['team_id'],'created_by'=>$userId,'match_at'=>date('Y-m-d H:i:s', strtotime((string)$data['match_at'])),
            'province'=>TurkeyLocation::canonicalProvince((string)$data['province'])??trim((string)$data['province']),'district'=>TurkeyLocation::canonicalDistrict((string)$data['province'],(string)$data['district'])??trim((string)$data['district']),'venue'=>trim((string)($data['venue'] ?? '')) ?: null,
            'position'=>(string)$data['position'],'players_needed'=>(int)$data['players_needed'],'level'=>(string)$data['level'],
            'fee'=>($data['fee_per_person'] ?? '') !== '' ? (float)$data['fee_per_person'] : null,'description'=>trim((string)($data['description'] ?? '')) ?: null,
        ]);
        return (int)Database::connection()->lastInsertId();
    }

    public static function search(array $filters = [], int $limit = 30): array
    {
        $sql='SELECT p.*, t.name AS team_name FROM player_wanted_posts p INNER JOIN teams t ON t.id=p.team_id WHERE p.status="open" AND p.match_at>=NOW()'; $params=[];
        foreach (['province','district','position','level'] as $key) {
            if (!empty($filters[$key])) { $sql .= " AND p.$key = :$key"; $params[$key]=trim((string)$filters[$key]); }
        }
        if (!empty($filters['date'])) { $sql .= ' AND DATE(p.match_at)=:match_date'; $params['match_date']=$filters['date']; }
        $sql .= ' ORDER BY p.match_at ASC LIMIT '.max(1,min(100,$limit));
        $stmt=Database::connection()->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.*, t.name AS team_name, t.captain_user_id FROM player_wanted_posts p INNER JOIN teams t ON t.id=p.team_id WHERE p.id=:id LIMIT 1'
        );
        $stmt->execute(['id'=>$id]);
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

}
