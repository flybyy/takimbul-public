<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Notification;
use App\Models\PlayerProfile;
use App\Models\Team;
use App\Models\TransferOffer;
use App\Models\User;
use RuntimeException;

final class TransferController
{
    public function index(): void
    {
        Auth::requireLogin(); $userId=(int)Auth::id();
        View::render('transfers/index',['title'=>'Transfer Teklifleri','incoming'=>TransferOffer::incomingForPlayer($userId),'outgoing'=>TransferOffer::outgoingForUser($userId)]);
    }

    public function offerForm(array $params): void
    {
        Auth::requireLogin(); $playerId=(int)($params['id']??0); $player=User::findPublic($playerId); $profile=PlayerProfile::find($playerId);
        if($player===null || $profile===null || ($profile['visibility']??'public')==='private' || $playerId===(int)Auth::id()){\App\Core\HttpError::render(404,'Oyuncu bulunamadı.');return;}
        $teams=Team::managedTeamsForUser((int)Auth::id());
        View::render('transfers/offer',['title'=>'Takıma Davet Et','player'=>$player,'profile'=>$profile,'teams'=>$teams]);
    }

    public function send(array $params): void
    {
        Auth::requireLogin(); if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $playerId=(int)($params['id']??0);$player=User::findPublic($playerId);$profile=PlayerProfile::find($playerId);$teams=Team::managedTeamsForUser((int)Auth::id());
        if($player===null || $profile===null || ($profile['visibility']??'public')==='private'){\App\Core\HttpError::render(404,'Oyuncu bulunamadı.');return;}
        $errors=TransferOffer::validateInput($_POST);$teamId=(int)($_POST['team_id']??0);
        if(!Team::canManageSporting($teamId,(int)Auth::id()))$errors['team_id']='Bu takım adına teklif gönderme yetkin yok.';
        if($errors!==[]){View::render('transfers/offer',['title'=>'Takıma Davet Et','player'=>$player,'profile'=>$profile,'teams'=>$teams,'errors'=>$errors,'old'=>$_POST]);return;}
        try{
            TransferOffer::create($teamId,$playerId,(int)Auth::id(),$_POST);$team=Team::find($teamId);
            Notification::create($playerId,'transfer_offer','Yeni takım teklifi',(string)($team['name']??'Bir takım').' seni kadrosuna davet etti.','/transferler');
            $_SESSION['flash_success']='Transfer teklifi oyuncuya gönderildi.';header('Location: /transferler');
        }catch(RuntimeException $e){View::render('transfers/offer',['title'=>'Takıma Davet Et','player'=>$player,'profile'=>$profile,'teams'=>$teams,'errors'=>['general'=>$e->getMessage()],'old'=>$_POST]);}
    }

    public function respond(array $params): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $id=(int)($params['id']??0);$status=(string)($_POST['status']??'');
        try{
            if($status==='accepted'){$result=TransferOffer::accept($id,(int)Auth::id());if($result){Notification::create((int)$result['offered_by_user_id'],'transfer_offer','Transfer teklifin kabul edildi',(string)$result['team_name'].' kadrosuna yeni oyuncu katıldı.','/transferler');$_SESSION['flash_success']='Teklifi kabul ettin. Takım kadrosuna eklendin.'.(!empty($result['jersey_warning'])?' '.(string)$result['jersey_warning']:'');}else $_SESSION['flash_error']='Teklif kabul edilemedi.';}
            elseif($status==='rejected'){$result=TransferOffer::reject($id,(int)Auth::id());if($result){Notification::create((int)$result['offered_by_user_id'],'transfer_offer','Transfer teklifin reddedildi','Oyuncu takım teklifini kabul etmedi.','/transferler');$_SESSION['flash_success']='Teklifi reddettin.';}else $_SESSION['flash_error']='Teklif reddedilemedi.';}
            else $_SESSION['flash_error']='Geçersiz transfer işlemi.';
        }catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}
        header('Location: /transferler');
    }

    public function withdraw(array $params): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $ok=TransferOffer::withdraw((int)($params['id']??0),(int)Auth::id());$_SESSION[$ok?'flash_success':'flash_error']=$ok?'Transfer teklifi geri çekildi.':'Teklif geri çekilemedi.';header('Location: /transferler');
    }
}
