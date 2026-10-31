<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Season
{
    public static function current(): ?array
    {
        $stmt=Database::connection()->query('SELECT * FROM seasons WHERE status="active" ORDER BY starts_at DESC LIMIT 1'); $row=$stmt->fetch(PDO::FETCH_ASSOC); return is_array($row)?$row:null;
    }
    public static function all(): array { return Database::connection()->query('SELECT * FROM seasons ORDER BY starts_at DESC')->fetchAll(PDO::FETCH_ASSOC) ?: []; }
}
