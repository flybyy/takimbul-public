<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Core\RateLimiter;
use App\Models\Report;
final class ReportController
{
    public function form(): void
    {
        Auth::requireLogin();
        View::render('reports/form',['title'=>'Şikâyet Et','targetType'=>(string)($_GET['type']??''),'targetId'=>(int)($_GET['id']??0),'returnTo'=>(string)($_GET['return_to']??'/')]);
    }
    public function store(): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        if(!RateLimiter::allow(RateLimiter::clientKey('report',(string)Auth::id()),10,3600)){http_response_code(429);$_SESSION['flash_error']='Çok fazla şikâyet gönderildi. Bir süre sonra tekrar dene.';header('Location: /');return;}
        $errors=Report::validate($_POST);if($errors){View::render('reports/form',['title'=>'Şikâyet Et','errors'=>$errors,'targetType'=>$_POST['target_type']??'','targetId'=>(int)($_POST['target_id']??0),'returnTo'=>$_POST['return_to']??'/']);return;}
        Report::create((int)Auth::id(),$_POST);$_SESSION['flash_success']='Şikâyetin inceleme için alındı.';
        $return=(string)($_POST['return_to']??'/');if(!str_starts_with($return,'/')||str_starts_with($return,'//'))$return='/';header('Location: '.$return);
    }
}
