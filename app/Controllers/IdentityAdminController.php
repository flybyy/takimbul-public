<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\HttpError;
use App\Core\View;
use App\Models\AdminAuditLog;
use App\Models\CoachProfile;
use App\Models\RefereeProfile;

final class IdentityAdminController
{
    private function gate(string $permission='moderation.manage'): void
    {
        AdminAuth::requirePermission($permission);
    }

    public function index(): void
    {
        $this->gate('moderation.manage');
        $referees=[];$coaches=[];
        try{$referees=RefereeProfile::adminList(150);}catch(\Throwable){}
        try{$coaches=CoachProfile::adminList(150);}catch(\Throwable){}
        View::render('admin/football-identities',[
            'title'=>'Futbol Kimlikleri',
            'referees'=>$referees,
            'coaches'=>$coaches,
        ]);
    }

    public function refereeShow(array $params): void
    {
        $this->gate('moderation.manage');
        $id=(int)($params['id']??0);$profile=RefereeProfile::adminFind($id);
        if(!$profile){HttpError::render(404,'Hakem profili bulunamadı.');return;}
        View::render('admin/referee-profile',['title'=>'Hakem #'.$id,'profile'=>$profile]);
    }

    public function refereeStatus(array $params): void
    {
        $this->gate('moderation.manage');
        if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}
        $id=(int)($params['id']??0);$profile=RefereeProfile::adminFind($id);
        if(!$profile){HttpError::render(404,'Hakem profili bulunamadı.');return;}
        $status=(string)($_POST['status']??'');$note=trim((string)($_POST['moderation_note']??''));
        $before=(string)($profile['status']??'');
        if(!RefereeProfile::setAdminStatus($id,$status,$note)){
            $_SESSION['flash_error']='Hakem profil durumu güncellenemedi.';
        }else{
            AdminAuditLog::record((int)AdminAuth::id(),'referee.profile_status_changed','referee_profile',$id,['from'=>$before,'to'=>$status]);
            $_SESSION['flash_success']='Hakem profil durumu güncellendi.';
        }
        header('Location: /admin/hakem/'.$id);
    }

    public function refereeLicense(array $params): void
    {
        $this->gate('moderation.manage');
        if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}
        $id=(int)($params['id']??0);$profile=RefereeProfile::adminFind($id);
        if(!$profile){HttpError::render(404,'Hakem profili bulunamadı.');return;}
        $status=(string)($_POST['license_status']??'');$before=(string)($profile['license_status']??'unverified');
        if(!RefereeProfile::setLicenseStatus($id,$status,(int)AdminAuth::id())){
            $_SESSION['flash_error']='Hakem lisans doğrulaması güncellenemedi.';
        }else{
            AdminAuditLog::record((int)AdminAuth::id(),'referee.license_status_changed','referee_profile',$id,['from'=>$before,'to'=>$status]);
            $_SESSION['flash_success']='Hakem lisans doğrulaması güncellendi.';
        }
        header('Location: /admin/hakem/'.$id);
    }

    public function coachShow(array $params): void
    {
        $this->gate('moderation.manage');
        $id=(int)($params['id']??0);$profile=CoachProfile::adminFind($id);
        if(!$profile){HttpError::render(404,'Teknik direktör profili bulunamadı.');return;}
        View::render('admin/coach-profile',['title'=>'Teknik Direktör #'.$id,'profile'=>$profile]);
    }

    public function coachStatus(array $params): void
    {
        $this->gate('moderation.manage');
        if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}
        $id=(int)($params['id']??0);$profile=CoachProfile::adminFind($id);
        if(!$profile){HttpError::render(404,'Teknik direktör profili bulunamadı.');return;}
        $status=(string)($_POST['status']??'');$note=trim((string)($_POST['moderation_note']??''));$before=(string)($profile['status']??'');
        if(!CoachProfile::setAdminStatus($id,$status,$note)){
            $_SESSION['flash_error']='Teknik direktör profil durumu güncellenemedi.';
        }else{
            AdminAuditLog::record((int)AdminAuth::id(),'coach.profile_status_changed','coach_profile',$id,['from'=>$before,'to'=>$status]);
            $_SESSION['flash_success']='Teknik direktör profil durumu güncellendi.';
        }
        header('Location: /admin/teknik-direktor/'.$id);
    }

    public function coachLicense(array $params): void
    {
        $this->gate('moderation.manage');
        if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}
        $id=(int)($params['id']??0);$profile=CoachProfile::adminFind($id);
        if(!$profile){HttpError::render(404,'Teknik direktör profili bulunamadı.');return;}
        $status=(string)($_POST['license_status']??'');$before=(string)($profile['license_status']??'unverified');
        if(!CoachProfile::setLicenseStatus($id,$status,(int)AdminAuth::id())){
            $_SESSION['flash_error']='Teknik direktör lisans doğrulaması güncellenemedi.';
        }else{
            AdminAuditLog::record((int)AdminAuth::id(),'coach.license_status_changed','coach_profile',$id,['from'=>$before,'to'=>$status]);
            $_SESSION['flash_success']='Teknik direktör lisans doğrulaması güncellendi.';
        }
        header('Location: /admin/teknik-direktor/'.$id);
    }
}
