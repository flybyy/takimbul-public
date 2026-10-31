<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Notification;

final class NotificationController
{
    public function index(): void
    {
        Auth::requireLogin();
        View::render('notifications/index',['title'=>'Bildirimler','notifications'=>Notification::recent((int)Auth::id())]);
    }

    public function read(array $params): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) { \App\Core\HttpError::render(419);return; }
        $link=Notification::markRead((int)($params['id'] ?? 0),(int)Auth::id());
        header('Location: '.($link ?? '/bildirimler'));
    }

    public function readAll(): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) { \App\Core\HttpError::render(419);return; }
        Notification::markAllRead((int)Auth::id());
        $_SESSION['flash_success']='Tüm bildirimler okundu olarak işaretlendi.';
        header('Location: /bildirimler');
    }
}
