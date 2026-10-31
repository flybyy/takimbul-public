<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\Csrf;use App\Core\RateLimiter;use App\Core\View;use App\Models\Notification;use App\Models\PlayerAvailability;use App\Models\PlayerProfile;use App\Models\Team;use App\Models\User;use RuntimeException;
final class AvailabilityController
{
    public function form(): void
    {
        Auth::requireLogin();$uid=(int)Auth::id();$identity=User::profileIdentity($uid);$profile=PlayerProfile::find($uid);
        View::render('availability/form',['title'=>'Müsaitim','current'=>PlayerAvailability::activeForUser($uid),'invites'=>PlayerAvailability::invitesForUser($uid),'identity'=>$identity,'profile'=>$profile,'errors'=>[]]);
    }
    public function save(): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}$uid=(int)Auth::id();if(!RateLimiter::allow(RateLimiter::clientKey('availability_publish',(string)$uid),12,3600)){http_response_code(429);$_SESSION['flash_error']='Çok sık müsaitlik güncelliyorsun. Bir süre sonra tekrar dene.';header('Location: /musaitim');return;}$errors=PlayerAvailability::validateInput($_POST);
        if($errors!==[]){View::render('availability/form',['title'=>'Müsaitim','current'=>PlayerAvailability::activeForUser($uid),'invites'=>PlayerAvailability::invitesForUser($uid),'identity'=>User::profileIdentity($uid),'profile'=>PlayerProfile::find($uid),'errors'=>$errors,'old'=>$_POST]);return;}
        try{PlayerAvailability::publish($uid,$_POST);$_SESSION['flash_success']='Müsaitlik durumun yayınlandı. Takımlar seni bu zaman aralığında bulabilir.';}catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}header('Location: /musaitim');
    }
    public function cancel(array $p): void
    { Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}PlayerAvailability::cancel((int)($p['id']??0),(int)Auth::id());$_SESSION['flash_success']='Müsaitlik yayından kaldırıldı.';header('Location: /musaitim'); }
    public function browse(): void
    {
        $filters=['province'=>trim((string)($_GET['province']??'')),'district'=>trim((string)($_GET['district']??'')),'position'=>trim((string)($_GET['position']??''))];
        if(Auth::id() && $filters['province']==='' && !isset($_GET['all'])){$i=User::profileIdentity((int)Auth::id());if(is_array($i)){$filters['province']=(string)$i['province'];$filters['district']=(string)$i['district'];}}
        View::render('availability/index',['title'=>'Müsait Oyuncular','players'=>PlayerAvailability::browse($filters),'filters'=>$filters,'managedTeams'=>Auth::id()?Team::managedTeamsForUser((int)Auth::id()):[]]);
    }
    public function invite(array $p): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}$uid=(int)Auth::id();if(!RateLimiter::allow(RateLimiter::clientKey('availability_invite',(string)$uid),30,3600)){http_response_code(429);$_SESSION['flash_error']='Çok fazla maç daveti gönderildi. Bir süre sonra tekrar dene.';header('Location: /musait-oyuncular');return;}$availabilityId=(int)($p['id']??0);$teamId=(int)($_POST['team_id']??0);
        try{$id=PlayerAvailability::createInvite($availabilityId,$teamId,$uid,(string)($_POST['message']??''));$availability=PlayerAvailability::find($availabilityId);$team=Team::find($teamId);if($availability&&$team)Notification::create((int)$availability['user_id'],'availability_invite','Maç davetin var',(string)$team['name'].' seni müsaitlik ilanından maça davet etti.','/musaitim');$_SESSION['flash_success']='Oyuncuya davet gönderildi.';}catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}
        header('Location: /musait-oyuncular');
    }
    public function respond(array $p): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}$status=(string)($_POST['status']??'');$ok=PlayerAvailability::respondInvite((int)($p['id']??0),(int)Auth::id(),$status);$_SESSION[$ok?'flash_success':'flash_error']=$ok?($status==='accepted'?'Daveti kabul ettin. Takım yöneticisiyle detayları netleştirebilirsin.':'Daveti reddettin.'):'Davet güncellenemedi.';header('Location: /musaitim');
    }
}
