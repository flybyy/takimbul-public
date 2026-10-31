<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $base = dirname(__DIR__) . '/Views/';
        $file = $base . ltrim($template, '/') . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View bulunamadı: {$template}");
        }

        extract($data, EXTR_SKIP);
        require $file;
    }
}
