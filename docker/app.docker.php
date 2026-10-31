<?php

declare(strict_types=1);

$env = static function (string $name, string $default = ''): string {
    $value = getenv($name);
    return $value === false || $value === '' ? $default : (string) $value;
};
$bool = static function (string $name, bool $default = false): bool {
    $value = getenv($name);
    if ($value === false || $value === '') return $default;
    return filter_var($value, FILTER_VALIDATE_BOOL);
};

return [
    'app' => [
        'name' => 'TakımBul',
        'url' => $env('APP_URL', 'http://localhost:8080'),
        'env' => $env('APP_ENV', 'local'),
        'debug' => $bool('APP_DEBUG', true),
        'timezone' => 'Europe/Istanbul',
    ],
    'auth' => [
        'require_email_verification' => false,
    ],
    'seo' => [
        'google_site_verification' => $env('GOOGLE_SITE_VERIFICATION'),
        'default_og_image' => '/assets/img/og-default.png',
    ],
    'analytics' => [
        'ga4_id' => $env('GA4_ID'),
    ],
    'support' => [
        'email' => $env('SUPPORT_EMAIL', 'destek@takimbul.com.tr'),
    ],
    'mail' => [
        'from' => $env('MAIL_FROM', 'noreply@takimbul.com.tr'),
        'from_name' => $env('MAIL_FROM_NAME', 'TakımBul'),
    ],
    'database' => [
        'host' => $env('DB_HOST', 'db'),
        'port' => (int) $env('DB_PORT', '3306'),
        'database' => $env('DB_DATABASE', 'takimbul'),
        'username' => $env('DB_USERNAME', 'takimbul'),
        'password' => $env('DB_PASSWORD', 'takimbul_local_password'),
    ],
];
