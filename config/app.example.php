<?php

return [
    'app' => [
        'name' => 'TakımBul',
        'url' => 'https://takimbul.com.tr',
        'env' => 'production',
        'debug' => false,
        'timezone' => 'Europe/Istanbul',
    ],
    'auth' => [
        'require_email_verification' => false,
    ],
    'seo' => [
        'google_site_verification' => '',
        'default_og_image' => '/assets/img/og-default.png',
    ],
    'analytics' => [
        'ga4_id' => '',
    ],
    'support' => [
        'email' => 'destek@takimbul.com.tr',
    ],
    'mail' => [
        'from' => 'noreply@takimbul.com.tr',
        'from_name' => 'TakımBul',
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'takimbul',
        'username' => 'takimbul_user',
        'password' => 'CHANGE_ME',
    ],
];
