<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\HttpError;
use App\Core\View;
use App\Models\AdminAuditLog;
use App\Models\NewsSponsor;

final class NewsSponsorController
{
    public function index(): void { AdminAuth::requirePermission('sponsors.manage');View::render('admin/news-sponsors',['title'=>'Haber Sponsorları','sponsors'=>NewsSponsor::all()]); }
    public function create(): void { AdminAuth::requirePermission('sponsors.manage');View::render('admin/news-sponsor-form',['title'=>'Yeni Haber Sponsoru','sponsor'=>[],'errors'=>[]]); }
    public function store(): void
    {
        AdminAuth::requirePermission('sponsors.manage');if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$data=self::input();$errors=NewsSponsor::validateInput($data);if($errors!==[]){http_response_code(422);View::render('admin/news-sponsor-form',['title'=>'Yeni Haber Sponsoru','sponsor'=>$data,'errors'=>$errors]);return;}$id=NewsSponsor::create($data,(int)AdminAuth::id());AdminAuditLog::record((int)AdminAuth::id(),'sponsor.created','news_sponsor',$id,['name'=>$data['name']]);$_SESSION['flash_success']='Haber sponsoru oluşturuldu.';header('Location: /admin/haber-sponsorlari/'.$id.'/duzenle');
    }
    public function edit(array $params): void { AdminAuth::requirePermission('sponsors.manage');$s=NewsSponsor::find((int)($params['id']??0));if(!$s){HttpError::render(404);return;}View::render('admin/news-sponsor-form',['title'=>'Sponsoru Düzenle','sponsor'=>$s,'errors'=>[]]); }
    public function update(array $params): void
    {
        AdminAuth::requirePermission('sponsors.manage');if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$id=(int)($params['id']??0);if(!NewsSponsor::find($id)){HttpError::render(404);return;}$data=self::input();$errors=NewsSponsor::validateInput($data);if($errors!==[]){$data['id']=$id;http_response_code(422);View::render('admin/news-sponsor-form',['title'=>'Sponsoru Düzenle','sponsor'=>$data,'errors'=>$errors]);return;}NewsSponsor::update($id,$data,(int)AdminAuth::id());AdminAuditLog::record((int)AdminAuth::id(),'sponsor.updated','news_sponsor',$id,['name'=>$data['name']]);$_SESSION['flash_success']='Sponsor güncellendi.';header('Location: /admin/haber-sponsorlari/'.$id.'/duzenle');
    }
    public function status(array $params): void { AdminAuth::requirePermission('sponsors.manage');if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$id=(int)($params['id']??0);$status=(string)($_POST['status']??'');if(!NewsSponsor::setStatus($id,$status,(int)AdminAuth::id())){$_SESSION['flash_error']='Sponsor durumu değiştirilemedi.';}else{AdminAuditLog::record((int)AdminAuth::id(),'sponsor.status_changed','news_sponsor',$id,['status'=>$status]);$_SESSION['flash_success']='Sponsor durumu güncellendi.';}header('Location: /admin/haber-sponsorlari'); }
    private static function input(): array { return ['name'=>(string)($_POST['name']??''),'logo_url'=>(string)($_POST['logo_url']??''),'website_url'=>(string)($_POST['website_url']??''),'description'=>(string)($_POST['description']??'')]; }
}
