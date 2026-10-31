<?php

declare(strict_types=1);

namespace App\Core;

final class HttpError
{
    /** @var array<int,string> */
    private const TITLES = [
        403 => 'Bu alana erişimin yok',
        404 => 'Sayfa bulunamadı',
        419 => 'Oturumun yenilenmesi gerekiyor',
        429 => 'Çok fazla istek gönderildi',
    ];

    public static function render(int $status, ?string $message = null, ?string $title = null): void
    {
        if (!in_array($status, [403, 404, 419, 429], true)) {
            $status = 500;
        }

        if (!headers_sent()) {
            http_response_code($status);
        }

        if ($status === 500) {
            $file = dirname(__DIR__) . '/Views/errors/500.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }

        $template = 'errors/' . $status;
        $viewFile = dirname(__DIR__) . '/Views/' . $template . '.php';
        if (!is_file($viewFile)) {
            $template = 'errors/404';
        }

        View::render($template, [
            'title' => $title ?? self::TITLES[$status] ?? 'İşlem tamamlanamadı',
            'message' => $message,
        ]);
    }
}
