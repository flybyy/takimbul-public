<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\AdminAuth;use App\Core\Auth;use App\Core\Csrf;use App\Core\RateLimiter;use App\Core\View;use App\Models\Career;use App\Models\PlayerProfile;use App\Models\PlayerValue;use App\Models\PlayerVideo;use App\Models\ScoutAccount;use App\Models\ScoutWatchlist;use App\Models\Team;use App\Models\TrustScore;use App\Models\User;use RuntimeException;
final class CareerController
{
    public function show(array $params): void
    {
        $userId=(int)($params['id']??0);$viewerId=Auth::id()?(int)Auth::id():0;$user=User::findPublic($userId);$profile=PlayerProfile::find($userId);
        if($user===null||$profile===null){$this->notFound();return;}$visibility=(string)($profile['visibility']??'public');$isOwner=$viewerId===$userId&&$viewerId>0;$isAdmin=AdminAuth::id()!==null;
        if($visibility==='private'&&!$isOwner&&!$isAdmin){$this->notFound();return;}if($visibility==='members'&&$viewerId===0){$this->notFound();return;}
        $scoutApproved=$viewerId>0?ScoutAccount::canDiscover($viewerId):false;
        View::render('career/show',['title'=>$user['display_name'].' · Kariyer','player'=>$user,'profile'=>$profile,'stats'=>Career::summary($userId),'teams'=>Team::activeMembershipsForUser($userId),'highlights'=>Career::highlights($userId),'videos'=>PlayerVideo::forUser($userId),'trust'=>TrustScore::forUser($userId),'value'=>PlayerValue::forUser($userId),'managedTeams'=>$viewerId>0&&$viewerId!==$userId?Team::managedTeamsForUser($viewerId):[],'scoutApproved'=>$scoutApproved,'watched'=>$scoutApproved?ScoutWatchlist::contains($viewerId,$userId):false,'viewerId'=>$viewerId]);
    }
    public function showcase(): void
    { Auth::requireLogin();$uid=(int)Auth::id();View::render('career/showcase',['title'=>'Video Vitrinim','videos'=>PlayerVideo::forUser($uid),'errors'=>[]]); }
    public function addVideo(): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $uid=(int)Auth::id();if(!RateLimiter::allow(RateLimiter::clientKey('video_showcase',(string)$uid),12,3600)){http_response_code(429);$_SESSION['flash_error']='Çok fazla vitrin işlemi yapıldı. Bir süre sonra tekrar dene.';header('Location: /kariyer/vitrin');return;}
        try{PlayerVideo::add($uid,(string)($_POST['title']??''),(string)($_POST['url']??''));$_SESSION['flash_success']='Video kariyer vitrinine eklendi.';}catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}header('Location: /kariyer/vitrin');
    }
    public function deleteVideo(array $params): void
    { Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}PlayerVideo::deleteOwned((int)($params['id']??0),(int)Auth::id());$_SESSION['flash_success']='Video vitrinden kaldırıldı.';header('Location: /kariyer/vitrin'); }
    private function notFound(): void { http_response_code(404);View::render('errors/404',['title'=>'Oyuncu Profili Bulunamadı']); }
}
