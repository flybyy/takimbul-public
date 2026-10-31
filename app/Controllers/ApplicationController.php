<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\PlayerApplication;
use App\Models\PlayerWantedPost;
use App\Models\Notification;
use RuntimeException;

final class ApplicationController
{
    public function applyForm(array $params): void
    {
        Auth::requireLogin();
        $post = PlayerWantedPost::find((int) ($params['id'] ?? 0));
        if ($post === null || $post['status'] !== 'open' || strtotime((string) $post['match_at']) <= time()) {
            \App\Core\HttpError::render(404,'İlan bulunamadı veya artık başvuru almıyor.');return;
        }
        if ((int) $post['captain_user_id'] === (int) Auth::id()) {
            $_SESSION['flash_error'] = 'Kendi takımının ilanına başvuramazsın.';
            header('Location: /oyuncu-bul');
            return;
        }
        View::render('applications/apply', ['title' => 'İlana Başvur', 'post' => $post]);
    }

    public function apply(array $params): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            \App\Core\HttpError::render(419,'Oturum doğrulaması başarısız.');return;
        }

        $post = PlayerWantedPost::find((int) ($params['id'] ?? 0));
        if ($post === null || $post['status'] !== 'open' || strtotime((string) $post['match_at']) <= time()) {
            \App\Core\HttpError::render(404,'İlan bulunamadı veya artık başvuru almıyor.');return;
        }
        if ((int) $post['captain_user_id'] === (int) Auth::id()) {
            $_SESSION['flash_error'] = 'Kendi takımının ilanına başvuramazsın.';
            header('Location: /oyuncu-bul');
            return;
        }

        $errors = PlayerApplication::validateInput($_POST);
        if ($errors !== []) {
            View::render('applications/apply', ['title' => 'İlana Başvur', 'post' => $post, 'errors' => $errors, 'old' => $_POST]);
            return;
        }

        try {
            PlayerApplication::create((int) $post['id'], (int) Auth::id(), (string) ($_POST['message'] ?? ''));
            Notification::create((int)$post['captain_user_id'],'application','Yeni oyuncu başvurusu',(string)(Auth::user()['name'] ?? 'Bir oyuncu').' · '.(string)$post['position'],'/basvurular');
            $_SESSION['flash_success'] = 'Başvurun takıma gönderildi.';
            header('Location: /basvurular');
        } catch (RuntimeException $e) {
            View::render('applications/apply', [
                'title' => 'İlana Başvur',
                'post' => $post,
                'errors' => ['general' => $e->getMessage()],
                'old' => $_POST,
            ]);
        }
    }

    public function index(): void
    {
        Auth::requireLogin();
        $userId = (int) Auth::id();
        View::render('applications/index', [
            'title' => 'Başvurular',
            'incoming' => PlayerApplication::incomingForCaptain($userId),
            'outgoing' => PlayerApplication::forUser($userId),
        ]);
    }

    public function updateStatus(array $params): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            \App\Core\HttpError::render(419);return;
        }
        $target = (string) ($_POST['status'] ?? '');
        $result = PlayerApplication::respond((int) ($params['id'] ?? 0), (int) Auth::id(), $target);
        if ($result === null) {
            $_SESSION['flash_error'] = 'Başvuru güncellenemedi veya bu işlem için yetkin yok.';
        } else {
            Notification::create((int)$result['user_id'],'application',$target==='accepted'?'Başvurun kabul edildi':'Başvurun sonuçlandı',(string)$result['team_name'].' · '.($target==='accepted'?'Maç kadrosundasın.':'Başvurun reddedildi.'),'/basvurular');
            $_SESSION['flash_success'] = $target === 'accepted' ? 'Oyuncu maç kadrosuna kabul edildi.' : 'Başvuru reddedildi.';
        }
        header('Location: /basvurular');
    }

    public function withdraw(array $params): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            \App\Core\HttpError::render(419);return;
        }
        if (PlayerApplication::withdraw((int) ($params['id'] ?? 0), (int) Auth::id())) {
            $_SESSION['flash_success'] = 'Başvurun geri çekildi.';
        } else {
            $_SESSION['flash_error'] = 'Başvuru geri çekilemedi.';
        }
        header('Location: /basvurular');
    }
}
