<?php

declare(strict_types=1);

namespace App\Core;

final class AppConfig
{
    private static ?array $config = null;

    public static function all(): array
    {
        if (self::$config !== null) return self::$config;
        $root = dirname(__DIR__, 2);
        $file = $root . '/config/app.php';
        if (!is_file($file)) $file = $root . '/config/app.example.php';
        $loaded = is_file($file) ? require $file : [];
        self::$config = is_array($loaded) ? $loaded : [];
        return self::$config;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::all();
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) return $default;
            $value = $value[$part];
        }
        return $value;
    }

    public static function env(): string
    {
        return (string) (getenv('APP_ENV') ?: self::get('app.env', 'production'));
    }

    public static function debug(): bool
    {
        $env = getenv('APP_DEBUG');
        if ($env !== false) return filter_var($env, FILTER_VALIDATE_BOOL);
        return (bool) self::get('app.debug', false);
    }

    public static function appUrl(): string
    {
        $url = (string) (getenv('APP_URL') ?: self::get('app.url', 'https://takimbul.com.tr'));
        return rtrim($url, '/');
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        $envKey = strtoupper(str_replace('.', '_', $key));
        $env = getenv($envKey);
        return $env !== false ? $env : self::get($key, $default);
    }
}
