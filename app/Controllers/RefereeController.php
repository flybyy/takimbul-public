<?php

declare(strict_types=1);
namespace App\Controllers;

use App\Core\Auth;use App\Core\Csrf;use App\Core\HttpError;use App\Core\View;use App\Models\FootballIdentity;use App\Models\RefereeProfile;use App\Models\User;use RuntimeException;use Throwable;

final class RefereeController
{
    public function index(): void{$filters=['province'=>trim((string)($_GET['province']??'')),'district'=>trim((string)($_GET['district']??'')),'classification'=>trim((string)($_GET['classification']??'')),'availability_status'=>trim((string)($_GET['availability_status']??'')),'verified'=>isset($_GET['verified'])?1:0,'experience_years'=>(int)($_GET['experience_years']??0)];$rows=[];try{$rows=RefereeProfile::search($filters);}catch(Throwable){}View::render('referees/index',['title'=>'Hakemler','referees'=>$rows,'filters'=>$filters]);}
    public function show(array $params): void{$profile=null;try{$profile=RefereeProfile::findPublicBySlug(trim((string)($params['slug']??'')));}catch(Throwable){}if(!$profile){HttpError::render(404,'Hakem profili bulunamadı.');return;}View::render('referees/show',['title'=>(string)$profile['display_name'].' - Hakem','refereeProfile'=>$profile,'referee'=>$profile]);}
    public function form(): void{Auth::requireLogin();$uid=(int)Auth::id();FootballIdentity::syncForUser($uid,['referee'],'referee');$profile=null;$identity=[];try{$profile=RefereeProfile::forOwner($uid);$identity=User::profileIdentity($uid)?:[];}catch(Throwable){}$form=$profile?:['province'=>$identity['province']??'','district'=>$identity['district']??'','classification'=>'','started_year'=>'','license_name'=>'','license_status'=>'unverified','availability_status'=>'showcase','fee_visibility'=>0,'fee_min'=>'','fee_max'=>'','competitions_text'=>'','bio'=>'','status'=>'draft'];View::render('referees/form',['title'=>'Hakem Profilim','profile'=>$form,'errors'=>[]]);}
    public function save(): void{Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$submit=(string)($_POST['action']??'draft')==='submit';$errors=RefereeProfile::validateInput($_POST);if($errors!==[]){View::render('referees/form',['title'=>'Hakem Profilim','profile'=>$_POST,'errors'=>$errors]);return;}try{RefereeProfile::save((int)Auth::id(),$_POST,$submit);$_SESSION['flash_success']=$submit?'Hakem profilin moderasyona gönderildi.':'Hakem profilin taslak olarak kaydedildi.';}catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}catch(Throwable){$_SESSION['flash_error']='Hakem profili kaydedilemedi.';}header('Location: /hakem-profilim');}
}
