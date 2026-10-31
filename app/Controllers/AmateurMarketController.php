<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\HttpError;
use App\Core\RateLimiter;
use App\Core\View;
use App\Models\AmateurCareerEntry;
use App\Models\AmateurPlayerAd;
use App\Models\Career;
use App\Models\PlayerProfile;
use App\Models\Team;
use App\Models\TeamFinder;
use App\Models\User;
use RuntimeException;
use Throwable;

final class AmateurMarketController
{
    public function index(): void
    {
        $filters=[
            'province'=>trim((string)($_GET['province']??'')),'district'=>trim((string)($_GET['district']??'')),
            'position'=>trim((string)($_GET['position']??'')),'dominant_foot'=>trim((string)($_GET['dominant_foot']??'')),
            'license_status'=>trim((string)($_GET['license_status']??'')),'level'=>trim((string)($_GET['level']??'')),
            'age_min'=>(int)($_GET['age_min']??0),'age_max'=>(int)($_GET['age_max']??0),'days'=>(int)($_GET['days']??0),
            'video'=>isset($_GET['video'])?1:0,'profile_intent'=>trim((string)($_GET['profile_intent']??'')),'sort'=>trim((string)($_GET['sort']??'newest')),
        ];
        $page=max(1,(int)($_GET['page']??1));$ads=[];$adContext=[];
        try{$ads=AmateurPlayerAd::publicList($filters,$page,24);if(Auth::id()!==null){$identity=User::profileIdentity((int)Auth::id());if($identity)$adContext=['province'=>(string)($identity['province']??''),'district'=>(string)($identity['district']??'')];}}catch(Throwable){}
        View::render('amateur-market/index',['title'=>'Amatör Futbolcular','ads'=>$ads,'filters'=>$filters,'page'=>$page,'adContext'=>$adContext]);
    }

    public function form(): void
    {
        Auth::requireLogin();$uid=(int)Auth::id();$ad=null;$identity=[];$profile=[];
        try{$ad=AmateurPlayerAd::forOwner($uid);$identity=User::profileIdentity($uid)?:[];$profile=PlayerProfile::find($uid)?:[];}catch(Throwable){}
        $form=$ad?:[
            'province'=>$identity['province']??'','district'=>$identity['district']??'','primary_position'=>$profile['primary_position']??'',
            'secondary_position'=>$profile['secondary_position']??'','birth_year'=>$profile['birth_year']??'','height_cm'=>$profile['height_cm']??'',
            'dominant_foot'=>$profile['dominant_foot']??'','license_status'=>'unknown','profile_intent'=>'both','desired_levels'=>[],'last_club'=>'','football_story'=>$profile['bio']??'',
            'preferred_cities'=>[],'availability_text'=>'','contact_preference'=>'takimbul','whatsapp_public'=>0,'video_url'=>'','status'=>'draft','career'=>[]
        ];
        View::render('amateur-market/form',['title'=>'Amatör Oyuncu Profilim','ad'=>$form,'careerRows'=>$ad['career']??[],'errors'=>[]]);
    }

    public function save(): void
    {
        Auth::requireLogin();
        if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}
        $this->persist(false);
    }

    public function publish(): void
    {
        Auth::requireLogin();
        if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}
        $this->persist(true);
    }

    private function persist(bool $submit): void
    {
        $uid=(int)Auth::id();
        if(!RateLimiter::allow(RateLimiter::clientKey($submit?'amateur_publish':'amateur_save',(string)$uid),$submit?6:40,3600)){http_response_code(429);$_SESSION['flash_error']='Çok fazla ilan işlemi yaptın. Bir süre sonra tekrar dene.';header('Location: /amator-ilanim');return;}
        $data=$_POST;$career=is_array($_POST['career']??null)?$_POST['career']:[];
        $birth=(int)($data['birth_year']??0);$age=AmateurPlayerAd::ageFromBirthYear($birth);if($age!==null&&$age<18)$data['whatsapp_public']=0;
        $errors=AmateurPlayerAd::validateInput($data)+AmateurCareerEntry::validateRows($career);
        if($errors!==[]){$data['desired_levels']=AmateurPlayerAd::normalizeList($data['desired_levels']??[]);$data['preferred_cities']=array_values(array_filter(array_map('trim',explode(',',(string)($data['preferred_cities']??'')))));View::render('amateur-market/form',['title'=>'Amatör Oyuncu Profilim','ad'=>$data,'careerRows'=>$career,'errors'=>$errors]);return;}
        try{AmateurPlayerAd::save($uid,$data,$career,$submit);$_SESSION['flash_success']=$submit?'Oyuncu profilin moderasyona gönderildi. Onaylandığında yayına girecek.':'Oyuncu profilin taslak olarak kaydedildi.';header('Location: /amator-ilanim');}catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();header('Location: /amator-ilanim');}catch(Throwable){$_SESSION['flash_error']='Oyuncu profili kaydedilirken beklenmeyen bir sorun oluştu.';header('Location: /amator-ilanim');}
    }

    public function pause(): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}try{AmateurPlayerAd::pause((int)Auth::id());$_SESSION['flash_success']='Oyuncu profilin duraklatıldı.';}catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}header('Location: /amator-ilanim');
    }

    public function renew(): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$uid=(int)Auth::id();if(!RateLimiter::allow(RateLimiter::clientKey('amateur_renew',(string)$uid),3,86400)){$_SESSION['flash_error']='Profil yenileme sınırına ulaştın.';header('Location: /amator-ilanim');return;}try{AmateurPlayerAd::renew($uid);$_SESSION['flash_success']='Oyuncu profilin yeniden yayına alındı.';}catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}header('Location: /amator-ilanim');
    }

    public function foundTeam(): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}try{AmateurPlayerAd::markFoundTeam((int)Auth::id());$_SESSION['flash_success']='Tebrikler! Transfer arayışın kapatıldı; oyuncu profilin vitrin olarak yayında kalacak.';}catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}header('Location: /amator-ilanim');
    }

    public function show(array $params): void
    {
        $slug=trim((string)($params['slug']??''));$ad=null;try{$ad=AmateurPlayerAd::findPublicBySlug($slug);}catch(Throwable){}if(!$ad){HttpError::render(404,'Amatör oyuncu profili bulunamadı.');return;}
        AmateurPlayerAd::recordView((int)$ad['id']);$age=AmateurPlayerAd::ageFromBirthYear((int)($ad['birth_year']??0));$whatsappUrl=null;$managedTeams=[];$favorite=false;$verifiedStats=[];
        try{$owner=User::profileIdentity((int)$ad['user_id']);if($owner&&AmateurPlayerAd::canExposeWhatsapp($ad,$age)&&!empty($owner['phone'])){$digits=preg_replace('/\D+/','',(string)$owner['phone'])??'';if($digits!=='')$whatsappUrl='https://wa.me/'.$digits;}$verifiedStats=Career::summary((int)$ad['user_id']);if(Auth::id()!==null){$managedTeams=Team::managedTeamsForUser((int)Auth::id());$favorite=AmateurPlayerAd::isFavorite((int)Auth::id(),(int)$ad['id']);}}catch(Throwable){}
        $intent=(string)($ad['profile_intent']??'team_search');$pageTitle=$intent==='showcase'?(string)$ad['display_name'].' Oyuncu Profili':($intent==='both'?(string)$ad['display_name'].' Oyuncu Vitrini ve Takım Arıyor':(string)$ad['display_name'].' Takım Arıyor');
        View::render('amateur-market/show',['title'=>$pageTitle,'amateurAd'=>$ad,'ad'=>$ad,'age'=>$age,'whatsappUrl'=>$whatsappUrl,'managedTeams'=>$managedTeams,'favorite'=>$favorite,'verifiedStats'=>$verifiedStats,'adContext'=>['province'=>(string)$ad['province'],'district'=>(string)($ad['district']??'')]]);
    }

    public function favorite(array $params): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$id=(int)($params['id']??0);$ad=AmateurPlayerAd::findPublicById($id);if(!$ad){HttpError::render(404);return;}AmateurPlayerAd::toggleFavorite((int)Auth::id(),$id);header('Location: /amator-futbolcu/'.rawurlencode((string)$ad['slug']));
    }

    public function invite(array $params): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$actor=(int)Auth::id();$id=(int)($params['id']??0);$ad=AmateurPlayerAd::findPublicById($id);if(!$ad){HttpError::render(404);return;}if(!RateLimiter::allow(RateLimiter::clientKey('amateur_team_invite',(string)$actor),25,3600)){$_SESSION['flash_error']='Çok fazla takım daveti gönderdin.';header('Location: /amator-futbolcu/'.rawurlencode((string)$ad['slug']));return;}try{if(!AmateurPlayerAd::isTransferOpen($ad))throw new RuntimeException('Bu oyuncu şu anda takım daveti kabul etmiyor.');TeamFinder::invite((int)($_POST['team_id']??0),$actor,(int)$ad['user_id'],(string)($_POST['message']??''));$_SESSION['flash_success']='Takım daveti gönderildi.';}catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}header('Location: /amator-futbolcu/'.rawurlencode((string)$ad['slug']));
    }

    public function contact(array $params): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$id=(int)($params['id']??0);$ad=AmateurPlayerAd::findPublicById($id);if(!$ad){HttpError::render(404);return;}$uid=(int)Auth::id();if(!RateLimiter::allow(RateLimiter::clientKey('amateur_contact',(string)$uid),30,3600)){$_SESSION['flash_error']='Çok fazla iletişim işlemi yaptın.';header('Location: /amator-futbolcu/'.rawurlencode((string)$ad['slug']));return;}AmateurPlayerAd::recordContact($id,$uid);header('Location: /mesaj/yeni/'.(int)$ad['user_id']);
    }
}
