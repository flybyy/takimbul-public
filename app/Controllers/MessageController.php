<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Conversation;
use App\Models\Notification;
use App\Models\User;
use RuntimeException;

final class MessageController
{
    public function index(): void
    {
        Auth::requireLogin();
        View::render('messages/index', ['title'=>'Mesajlar','threads'=>Conversation::threadsForUser((int)Auth::id())]);
    }

    public function newForm(array $params): void
    {
        Auth::requireLogin();
        $recipientId=(int)($params['id'] ?? 0);
        $errors=Conversation::validateRecipient((int)Auth::id(),$recipientId);
        $recipient=$errors===[] ? User::findPublic($recipientId) : null;
        if ($recipient===null) {
            \App\Core\HttpError::render(404,'Mesaj göndermek istediğin kullanıcı bulunamadı.');return;
        }
        View::render('messages/new',['title'=>'Yeni Mesaj','recipient'=>$recipient]);
    }

    public function start(array $params): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) { \App\Core\HttpError::render(419);return; }
        $recipientId=(int)($params['id'] ?? 0);
        $recipient=User::findPublic($recipientId);
        $errors=Conversation::validateRecipient((int)Auth::id(),$recipientId)+Conversation::validateMessage((string)($_POST['body'] ?? ''));
        if ($recipient===null) $errors['recipient']='Kullanıcı bulunamadı.';
        if ($errors!==[]) {
            View::render('messages/new',['title'=>'Yeni Mesaj','recipient'=>$recipient,'errors'=>$errors,'old'=>$_POST]);
            return;
        }
        try {
            $threadId=Conversation::send((int)Auth::id(),$recipientId,(string)$_POST['body']);
            Notification::create($recipientId,'message','Yeni mesajın var',(string)(Auth::user()['name'] ?? 'Bir kullanıcı').' sana mesaj gönderdi.','/mesajlar/'.$threadId);
            header('Location: /mesajlar/'.$threadId);
        } catch (RuntimeException $e) {
            View::render('messages/new',['title'=>'Yeni Mesaj','recipient'=>$recipient,'errors'=>['body'=>$e->getMessage()],'old'=>$_POST]);
        }
    }

    public function show(array $params): void
    {
        Auth::requireLogin();
        $thread=Conversation::findForUser((int)($params['id'] ?? 0),(int)Auth::id());
        if ($thread===null) {
            \App\Core\HttpError::render(404,'Konuşma bulunamadı.');return;
        }
        View::render('messages/show',['title'=>(string)$thread['other_name'],'thread'=>$thread]);
    }

    public function send(array $params): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) { \App\Core\HttpError::render(419);return; }
        $threadId=(int)($params['id'] ?? 0);
        $otherUserId=Conversation::otherUserId($threadId,(int)Auth::id());
        if ($otherUserId===null) {
            http_response_code(404);
            return;
        }
        $errors=Conversation::validateMessage((string)($_POST['body'] ?? ''));
        if ($errors!==[]) {
            $_SESSION['flash_error']=$errors['body'];
            header('Location: /mesajlar/'.$threadId);
            return;
        }
        $threadId=Conversation::send((int)Auth::id(),$otherUserId,(string)$_POST['body']);
        Notification::create($otherUserId,'message','Yeni mesajın var',(string)(Auth::user()['name'] ?? 'Bir kullanıcı').' sana mesaj gönderdi.','/mesajlar/'.$threadId);
        header('Location: /mesajlar/'.$threadId);
    }
}
