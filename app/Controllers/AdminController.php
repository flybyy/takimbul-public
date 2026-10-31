<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\View;
use App\Models\Ad;
use App\Models\News;
use App\Models\AmateurPlayerAd;
use App\Models\Admin;
use App\Models\UserToken;
use App\Models\AdminUserNote;
use App\Models\AdminAuditLog;
use App\Models\Report;
use App\Models\ScoutAccount;
use App\Models\SiteSetting;
use App\Models\Team;
use App\Models\User;
use PDO;

final class AdminController
{
    private function gate(?string $permission=null): void { if($permission!==null)AdminAuth::requirePermission($permission);else AdminAuth::requireLogin(); }

    public function showLogin(): void
    {
        if(AdminAuth::id()!==null){header('Location: /admin');return;}
        View::render('admin/auth/login',['title'=>'Yönetici Girişi']);
    }

    public function login(): void
    {
        if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419,'Oturum doğrulaması başarısız.');return;}
        $identifier=trim((string)($_POST['identifier']??''));
        if(!RateLimiter::allow(RateLimiter::clientKey('admin_login',$identifier),8,900)){
            http_response_code(429);
            View::render('admin/auth/login',['title'=>'Yönetici Girişi','error'=>'Çok fazla giriş denemesi. 15 dakika sonra tekrar dene.']);
            return;
        }
        $admin=Admin::findByIdentifier($identifier);
        if(!$admin || ($admin['status']??'')!=='active' || !password_verify((string)($_POST['password']??''),(string)$admin['password_hash'])){
            View::render('admin/auth/login',['title'=>'Yönetici Girişi','error'=>'Yönetici kullanıcı adı/e-posta veya şifre hatalı.']);
            return;
        }
        AdminAuth::login($admin);
        Admin::markLogin((int)$admin['id']);
        header('Location: /admin');
    }

    public function logout(): void
    {
        if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        AdminAuth::logout();
        header('Location: /admin/giris');
    }

    public function index(): void
    {
        AdminAuth::requirePermission('dashboard.editorial');
        $recentNews=[];
        try{$recentNews=array_slice(News::allForAdmin((int)AdminAuth::id(),!AdminAuth::can('news.edit_any')),0,8);}catch(\Throwable){}
        if(!AdminAuth::can('dashboard')){
            View::render('admin/writer-dashboard',['title'=>'Yazar Paneli','recentNews'=>$recentNews]);
            return;
        }
        $stats=User::adminStats();
        $canAds=AdminAuth::can('ads.manage');$canSettings=AdminAuth::can('settings.manage');
        View::render('admin/index',[
            'title'=>'Yönetim Merkezi','stats'=>$stats,'operations'=>$this->operations(),
            'systemHealth'=>$canSettings?$this->systemHealth():[],
            'recentUsers'=>User::recentForAdmin(8),'recentTeams'=>Team::recent(6),
            'recentAds'=>$canAds?array_slice(Ad::allForAdmin(),0,5):[],
            'recentNews'=>$recentNews,
            'reportCount'=>(int)($stats['open_reports']??count(Report::allOpen())),
            'scoutCount'=>(int)($stats['pending_scouts']??count(ScoutAccount::pending())),
        ]);
    }

    public function auditLog(): void
    {
        $this->gate('audit.view');
        View::render('admin/audit-log',['title'=>'Audit Log','entries'=>AdminAuditLog::recent(150)]);
    }
    public function users(): void
    {
        $this->gate('moderation.manage');
        $filters=['q'=>trim((string)($_GET['q']??'')),'status'=>trim((string)($_GET['status']??'')),'province'=>trim((string)($_GET['province']??''))];
        $users=($filters['status']===''&&$filters['province']==='')?User::adminSearch($filters['q'],100):User::adminFilteredSearch($filters);
        View::render('admin/users',['title'=>'Kullanıcı Yönetimi','users'=>$users,'filters'=>$filters,'stats'=>User::adminStats()]);
    }
    public function amateurAds(): void
    {
        $this->gate('moderation.manage');
        $filters=['q'=>trim((string)($_GET['q']??'')),'status'=>trim((string)($_GET['status']??'')),'province'=>trim((string)($_GET['province']??'')),'position'=>trim((string)($_GET['position']??'')),'license_status'=>trim((string)($_GET['license_status']??'')),'profile_intent'=>trim((string)($_GET['profile_intent']??''))];
        View::render('admin/amateur-ads',['title'=>'Amatör Futbolcu İlanları','ads'=>AmateurPlayerAd::adminList($filters),'filters'=>$filters,'counts'=>AmateurPlayerAd::adminCounts()]);
    }
    public function amateurAdShow(array $p): void
    {
        $this->gate('moderation.manage');$id=(int)($p['id']??0);$ad=AmateurPlayerAd::adminFind($id);if(!$ad){\App\Core\HttpError::render(404,'Amatör futbolcu ilanı bulunamadı.');return;}View::render('admin/amateur-ad-show',['title'=>'Amatör İlan #'.$id,'ad'=>$ad]);
    }
    public function amateurAdStatus(array $p): void
    {
        $this->gate('moderation.manage');if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}$id=(int)($p['id']??0);try{AmateurPlayerAd::adminSetStatus($id,(string)($_POST['status']??''),(int)AdminAuth::id(),(string)($_POST['moderation_note']??''));$_SESSION['flash_success']='İlan moderasyon durumu güncellendi.';}catch(\RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}header('Location: /admin/amator-ilan/'.$id);
    }
    public function amateurAdFeature(array $p): void
    {
        $this->gate('moderation.manage');if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}$id=(int)($p['id']??0);AmateurPlayerAd::setFeatured($id,(int)($_POST['days']??0),(int)AdminAuth::id());$_SESSION['flash_success']='Öne çıkarma ayarı güncellendi.';header('Location: /admin/amator-ilan/'.$id);
    }
    public function reports(): void { $this->gate('moderation.manage');View::render('admin/reports',['title'=>'Şikâyetler','reports'=>Report::allOpen()]); }
    public function reportStatus(array $p): void { $this->gate('moderation.manage');if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}Report::updateStatus((int)($p['id']??0),(string)($_POST['status']??''));header('Location: /admin/sikayetler'); }
    public function userStatus(array $p): void { $this->gate('moderation.manage');if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}$status=(string)($_POST['status']??'');if(in_array($status,['active','suspended'],true))User::setStatus((int)($p['id']??0),$status);$return=(string)($_POST['return_to']??'/admin/kullanicilar');if(!str_starts_with($return,'/admin/'))$return='/admin/kullanicilar';header('Location: '.$return); }
    public function userDetail(array $p): void
    {
        $this->gate('moderation.manage');$id=(int)($p['id']??0);$user=User::adminDetail($id);
        if(!$user){\App\Core\HttpError::render(404,'Kullanıcı bulunamadı.');return;}
        $resetLink=$_SESSION['admin_reset_link']??null;unset($_SESSION['admin_reset_link']);
        View::render('admin/user-detail',['title'=>'Kullanıcı #'.$id,'user'=>$user,'notes'=>AdminUserNote::forUser($id),'resetLink'=>$resetLink,'errors'=>[]]);
    }
    public function userVerification(array $p): void
    {
        AdminAuth::requirePermission('players.verify');
        if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $id=(int)($p['id']??0);$user=User::adminDetail($id);if(!$user){\App\Core\HttpError::render(404);return;}
        $verify=(string)($_POST['verified']??'0')==='1';$before=!empty($user['profile_verified_at']);
        if(!User::setProfileVerified($id,$verify,(int)AdminAuth::id())){
            $_SESSION['flash_error']='Profil doğrulaması kaydedilemedi. V10.7 veritabanı güncellemesini kontrol edip production-repair komutunu çalıştırın.';
            header('Location: /admin/kullanici/'.$id);return;
        }
        if($before!==$verify){AdminAuditLog::record((int)AdminAuth::id(),$verify?'player.verification_granted':'player.verification_revoked','user',$id,['previous'=>$before,'verified'=>$verify]);}
        $_SESSION['flash_success']=$verify?'Profil doğrulandı.':'Profil doğrulaması kaldırıldı.';
        header('Location: /admin/kullanici/'.$id);
    }

    public function userContact(array $p): void
    {
        $this->gate('moderation.manage');if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $id=(int)($p['id']??0);$errors=User::adminUpdateContact($id,$_POST);
        if($errors!==[]){$user=User::adminDetail($id);if(!$user){\App\Core\HttpError::render(404);return;}View::render('admin/user-detail',['title'=>'Kullanıcı #'.$id,'user'=>array_merge($user,$_POST),'notes'=>AdminUserNote::forUser($id),'resetLink'=>null,'errors'=>$errors]);return;}
        $_SESSION['flash_success']='Kullanıcının iletişim bilgileri güncellendi.';header('Location: /admin/kullanici/'.$id);
    }
    public function userNote(array $p): void
    {
        $this->gate('moderation.manage');if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $id=(int)($p['id']??0);$error=AdminUserNote::add($id,(int)AdminAuth::id(),(string)($_POST['note']??''));
        if($error!==null)$_SESSION['flash_error']=$error;else $_SESSION['flash_success']='Yönetim notu eklendi.';header('Location: /admin/kullanici/'.$id);
    }
    public function userReset(array $p): void
    {
        $this->gate('moderation.manage');if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $id=(int)($p['id']??0);$user=User::adminDetail($id);if(!$user){\App\Core\HttpError::render(404);return;}
        $token=UserToken::issue($id,'password_reset',3600);$_SESSION['admin_reset_link']=\App\Core\AppConfig::appUrl().'/sifre-sifirla?token='.$token;$_SESSION['flash_success']='1 saat geçerli şifre sıfırlama bağlantısı oluşturuldu.';header('Location: /admin/kullanici/'.$id);
    }
    public function scouts(): void { $this->gate('moderation.manage');View::render('admin/scouts',['title'=>'Scout Başvuruları','accounts'=>ScoutAccount::pending()]); }
    public function scoutStatus(array $p): void { $this->gate('moderation.manage');if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}$status=(string)($_POST['status']??'');if(in_array($status,['approved','rejected'],true))ScoutAccount::review((int)($p['id']??0),$status,(int)AdminAuth::id());header('Location: /admin/scout-basvurulari'); }
    public function settings(): void { $this->gate('settings.manage');View::render('admin/settings',['title'=>'Site Ayarları','settings'=>SiteSetting::all(),'systemHealth'=>$this->systemHealth(),'errors'=>[]]); }
    public function updateSettings(): void
    {
        $this->gate('settings.manage');if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $values=[
            'announcement_enabled'=>isset($_POST['announcement_enabled'])?'1':'0',
            'announcement_text'=>trim((string)($_POST['announcement_text']??'')),
            'support_email'=>trim((string)($_POST['support_email']??'')),
            'home_feature_availability'=>isset($_POST['home_feature_availability'])?'1':'0',
            'home_feature_rival_match'=>isset($_POST['home_feature_rival_match'])?'1':'0',
            'require_email_verification'=>isset($_POST['require_email_verification'])?'1':'0',
            'maintenance_message'=>trim((string)($_POST['maintenance_message']??'')),
            'welcome_ad_enabled'=>isset($_POST['welcome_ad_enabled'])?'1':'0',
        ];
        $errors=SiteSetting::setMany($values,(int)AdminAuth::id());if($errors!==[]){View::render('admin/settings',['title'=>'Site Ayarları','settings'=>$values,'systemHealth'=>$this->systemHealth(),'errors'=>$errors]);return;}$_SESSION['flash_success']='Site ayarları güncellendi.';header('Location: /admin/ayarlar');
    }
    private function operations(): array
    {
        try{
            $pdo=Database::connection();
            $matches=$pdo->query('SELECT m.id,m.match_at,m.status,h.name home_team,a.name away_team,m.district FROM matches m INNER JOIN teams h ON h.id=m.home_team_id INNER JOIN teams a ON a.id=m.away_team_id WHERE DATE(m.match_at)=CURDATE() ORDER BY m.match_at ASC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC)?:[];
            $availability=$pdo->query('SELECT a.id,a.starts_at,a.ends_at,u.display_name,a.position,a.district FROM player_availabilities a INNER JOIN users u ON u.id=a.user_id WHERE a.status="active" AND a.ends_at>=NOW() ORDER BY a.starts_at ASC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC)?:[];
            return ['today_matches'=>$matches,'availabilities'=>$availability];
        }catch(\Throwable){return ['today_matches'=>[],'availabilities'=>[]];}
    }
    private function systemHealth(): array
    {
        $db=false;try{$db=(int)Database::connection()->query('SELECT 1')->fetchColumn()===1;}catch(\Throwable){}
        $logDir=dirname(__DIR__,2).'/storage/logs';return ['php'=>PHP_VERSION,'pdo_mysql'=>extension_loaded('pdo_mysql'),'database'=>$db,'logs_writable'=>is_dir($logDir)&&is_writable($logDir),'environment'=>(string)\App\Core\AppConfig::get('app.env','production')];
    }
}
