<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\TeamFormation;
use App\Models\TeamStats;
use App\Models\TeamStaffMembership;
use App\Models\Notification;
use RuntimeException;

final class TeamController
{
    public function index(): void
    {
        View::render('teams/index', ['title' => 'Takımlar', 'teams' => Team::recent()]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        View::render('teams/create', ['title' => 'Takım Oluştur']);
    }

    public function store(): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            \App\Core\HttpError::render(419,'Oturum doğrulaması başarısız.');return;
        }

        $errors = Team::validateInput($_POST);
        if ($errors !== []) {
            View::render('teams/create', ['title' => 'Takım Oluştur', 'errors' => $errors, 'old' => $_POST]);
            return;
        }

        $teamId = Team::create((int) Auth::id(), $_POST);
        $_SESSION['flash_success'] = 'Takımın kuruldu. Artık oyuncu ve rakip arayabilirsin.';
        header('Location: /takim/' . $teamId);
    }

    public function show(array $params): void
    {
        $team = Team::find((int) ($params['id'] ?? 0));
        if ($team === null) {
            \App\Core\HttpError::render(404,'Takım bulunamadı.');return;
        }

        $rosterStats=TeamStats::forRoster((int)$team['id'],(array)$team['roster']);
        foreach($team['roster'] as &$member){$member=array_merge($member,$rosterStats[(int)$member['id']]??[]);}unset($member);
        $stats=TeamStats::forTeam((int)$team['id']);
        $leaders=TeamStats::leaders((array)$team['roster']);
        $formation=TeamFormation::find((int)$team['id']);
        View::render('teams/show', ['title' => $team['name'], 'team' => $team,'stats'=>$stats,'leaders'=>$leaders,'formation'=>$formation,'technicalStaff'=>TeamStaffMembership::activeForTeam((int)$team['id']), 'viewerCanManage'=>Auth::id()?Team::canManageSporting((int)$team['id'],(int)Auth::id()):false, 'viewerIsCaptain'=>Auth::id()?Team::isCaptain((int)$team['id'],(int)Auth::id()):false]);
    }
    public function manage(array $params): void
    {
        Auth::requireLogin(); $team=Team::find((int)($params['id']??0));
        if($team===null){\App\Core\HttpError::render(404,'Takım bulunamadı.');return;}
        if(!Team::isCaptain((int)$team['id'],(int)Auth::id())){\App\Core\HttpError::render(403,'Takım yönetimi yalnızca kaptana açıktır.');return;}
        $savedFormation=TeamFormation::find((int)$team['id']);$templates=TeamFormation::templates();
        $requestedFormat=(string)($_GET['format']??($savedFormation['match_format']??'7v7'));if(!isset($templates[$requestedFormat]))$requestedFormat='7v7';
        View::render('teams/manage',['title'=>$team['name'].' · Takım Yönetimi','team'=>$team,'technicalStaff'=>TeamStaffMembership::activeForTeam((int)$team['id']),'pendingStaff'=>TeamStaffMembership::pendingForUser((int)Auth::id()),'formation'=>$savedFormation,'formationTemplates'=>$templates,'formationFormat'=>$requestedFormat,'formationTemplate'=>$templates[$requestedFormat]]);
    }


    public function inviteStaff(array $params): void{Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}$teamId=(int)($params['id']??0);try{$membershipId=TeamStaffMembership::add($teamId,(int)Auth::id(),(int)($_POST['coach_user_id']??0),(string)($_POST['staff_role']??'other'));Notification::create((int)($_POST['coach_user_id']??0),'team_staff_invite','Teknik ekip daveti','Bir takım seni teknik ekibine davet etti.','/hesabim');$_SESSION['flash_success']='Teknik ekip daveti gönderildi.';}catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}header('Location: /takim/'.$teamId.'/yonet#teknik-ekip');}
    public function leaveStaff(array $params): void{Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}$teamId=(int)($params['id']??0);try{TeamStaffMembership::leave((int)($params['membershipId']??0),(int)Auth::id());$_SESSION['flash_success']='Teknik ekip üyeliği sonlandırıldı.';}catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}header('Location: /takim/'.$teamId.'/yonet#teknik-ekip');}
    public function respondStaff(array $params): void{Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}$ok=TeamStaffMembership::respond((int)($params['id']??0),(int)Auth::id(),(string)($_POST['decision']??''));$_SESSION[$ok?'flash_success':'flash_error']=$ok?'Teknik ekip daveti yanıtlandı.':'Davet yanıtlanamadı.';header('Location: /hesabim');}

    public function updateClubSettings(array $params): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $teamId=(int)($params['id']??0);
        try{Team::updateClubSettings($teamId,(int)Auth::id(),$_POST);$_SESSION['flash_success']='Kulüp bilgileri güncellendi.';}
        catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}
        header('Location: /takim/'.$teamId.'/yonet#kulup');
    }
    public function updateMember(array $params): void
    {
        Auth::requireLogin(); if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $teamId=(int)($params['id']??0);$memberId=(int)($params['userId']??0);
        try{TeamMembership::updateSettings($teamId,(int)Auth::id(),$memberId,$_POST);$_SESSION['flash_success']='Oyuncu takım ayarları güncellendi.';}
        catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}
        header('Location: /takim/'.$teamId.'/yonet');
    }

    public function saveFormation(array $params): void
    {
        Auth::requireLogin();
        if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $teamId=(int)($params['id']??0);
        try{
            TeamFormation::save($teamId,(int)Auth::id(),(string)($_POST['match_format']??''),(string)($_POST['formation_code']??''),(array)($_POST['slots']??[]));
            $_SESSION['flash_success']='Takım dizilişi kaydedildi.';
        }catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}
        header('Location: /takim/'.$teamId.'/yonet#dizilis');
    }

}
