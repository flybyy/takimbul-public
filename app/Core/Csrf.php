<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf'];
    }

    public static function validate(?string $token): bool
    {
        $active = $_SESSION['_csrf'] ?? null;
        return is_string($active) && is_string($token) && $token !== '' && hash_equals($active, $token);
    }
}
