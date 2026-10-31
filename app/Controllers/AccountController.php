<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\Csrf;use App\Core\View;use App\Models\FootballIdentity;use App\Models\PlayerProfile;use App\Models\User;
final class AccountController
{
    public function index(): void{Auth::requireLogin();$uid=(int)Auth::id();View::render('account/index',['title'=>'Hesabım','account'=>User::accountDetails($uid),'identities'=>FootballIdentity::forUser($uid),'errors'=>[]]);}

    public function onboarding(): void
    {
        Auth::requireLogin();
        $id=(int)Auth::id();$account=User::accountDetails($id)??[];$profile=PlayerProfile::find($id)??[];
        $checks=[
            'identity'=>!empty($account['display_name'])&&!empty($account['province'])&&!empty($account['district']),
            'position'=>!empty($profile['primary_position']),
            'avatar'=>!empty($profile['avatar_path']),
            'bio'=>!empty($profile['bio']),
        ];
        $complete=(int)round((count(array_filter($checks))/count($checks))*100);
        View::render('account/onboarding',['title'=>'Başlangıç','account'=>$account,'profile'=>$profile,'identities'=>FootballIdentity::forUser($id),'completion'=>$complete,'checks'=>$checks]);
    }

    public function addFootballIdentity(): void{Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $type=trim((string)($_POST['identity_type']??''));if(!FootballIdentity::isValidType($type)){$_SESSION['flash_error']='Geçersiz futbol kimliği.';header('Location: /hesabim');return;}
        FootballIdentity::syncForUser((int)Auth::id(),[$type],$type);$_SESSION['flash_success']=FootballIdentity::label($type).' kimliği hesabına eklendi.';header('Location: '.FootballIdentity::onboardingPathForType($type));}

    public function updateContact(): void{Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}$errors=User::validateContact($_POST,(int)Auth::id());if($errors!==[]){View::render('account/index',['title'=>'Hesabım','account'=>array_merge(User::accountDetails((int)Auth::id())?:[],$_POST),'errors'=>$errors]);return;}User::updateOwnContact((int)Auth::id(),$_POST);$_SESSION['user']['email']=strtolower(trim((string)$_POST['email']));$_SESSION['flash_success']='İletişim bilgilerin güncellendi.';header('Location: /hesabim');}
}
