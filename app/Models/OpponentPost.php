<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\TurkeyLocation;
use PDO;

final class OpponentPost
{
    public const LEVELS = ['Eğlence', 'Orta', 'Rekabetçi'];
    public const FORMATS = ['5v5', '6v6', '7v7', '8v8'];

    public static function validateInput(array $data): array
    {
        $errors=[]; $matchAt=trim((string)($data['match_at'] ?? ''));
        if ($matchAt==='' || strtotime($matchAt)===false || strtotime($matchAt)<=time()) $errors['match_at']='Maç tarihi gelecekte olmalı.';
        $province=trim((string)($data['province']??''));$district=trim((string)($data['district']??''));
        if($province===''||TurkeyLocation::canonicalProvince($province)===null)$errors['province']='Geçerli bir il seç.';
        elseif($district===''||!TurkeyLocation::isValidPair($province,$district))$errors['district']='Geçerli bir ilçe seç.';
        if (!in_array((string)($data['level'] ?? ''), self::LEVELS,true)) $errors['level']='Geçerli bir seviye seç.';
        if (!in_array((string)($data['match_format'] ?? ''), self::FORMATS,true)) $errors['match_format']='Geçerli bir maç formatı seç.';
        if (strlen(trim((string)($data['description'] ?? '')))>500) $errors['description']='Açıklama 500 karakteri geçemez.';
        return $errors;
    }

    public static function create(int $userId, array $data): int
    {
        $stmt=Database::connection()->prepare('INSERT INTO opponent_posts (team_id, created_by, match_at, province, district, venue, level, match_format, description) VALUES (:team_id,:created_by,:match_at,:province,:district,:venue,:level,:match_format,:description)');
        $stmt->execute(['team_id'=>(int)$data['team_id'],'created_by'=>$userId,'match_at'=>date('Y-m-d H:i:s',strtotime((string)$data['match_at'])),'province'=>TurkeyLocation::canonicalProvince((string)$data['province'])??trim((string)$data['province']),'district'=>TurkeyLocation::canonicalDistrict((string)$data['province'],(string)$data['district'])??trim((string)$data['district']),'venue'=>trim((string)($data['venue'] ?? '')) ?: null,'level'=>(string)$data['level'],'match_format'=>(string)$data['match_format'],'description'=>trim((string)($data['description'] ?? '')) ?: null]);
        return (int)Database::connection()->lastInsertId();
    }

    public static function search(array $filters = [], int $limit = 30): array
    {
        $sql='SELECT p.*, t.name AS team_name FROM opponent_posts p INNER JOIN teams t ON t.id=p.team_id WHERE p.status="open" AND p.match_at>=NOW()'; $params=[];
        foreach (['province','district','level','match_format'] as $key) { if (!empty($filters[$key])) { $sql .= " AND p.$key = :$key"; $params[$key]=trim((string)$filters[$key]); } }
        if (!empty($filters['date'])) { $sql.=' AND DATE(p.match_at)=:match_date'; $params['match_date']=$filters['date']; }
        $sql.=' ORDER BY p.match_at ASC LIMIT '.max(1,min(100,$limit));
        $stmt=Database::connection()->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    public static function find(int $id): ?array
    {
        $stmt=Database::connection()->prepare(
            'SELECT p.*, t.name AS team_name, t.captain_user_id FROM opponent_posts p INNER JOIN teams t ON t.id=p.team_id WHERE p.id=:id LIMIT 1'
        );
        $stmt->execute(['id'=>$id]);
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

}
