<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\HttpError;
use App\Core\View;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\User;

final class StaffController
{
    public function index(): void { AdminAuth::requirePermission('staff.manage');View::render('admin/staff',['title'=>'Ekip & Yetkiler','staff'=>Admin::allStaff()]); }
    public function create(): void { AdminAuth::requirePermission('staff.manage');View::render('admin/staff-form',['title'=>'Yeni Ekip Üyesi','staffMember'=>self::defaults(),'errors'=>[]]); }
    public function store(): void
    {
        AdminAuth::requirePermission('staff.manage');if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$data=self::input(true);$errors=Admin::validateStaffInput($data,null,true);if($errors!==[]){http_response_code(422);View::render('admin/staff-form',['title'=>'Yeni Ekip Üyesi','staffMember'=>$data,'errors'=>$errors]);return;}$id=Admin::createStaff($data);AdminAuditLog::record((int)AdminAuth::id(),'staff.created','admin',$id,['role'=>$data['role'],'status'=>$data['status'],'display_name'=>$data['display_name']]);$_SESSION['flash_success']='Ekip üyesi oluşturuldu.';header('Location: /admin/ekip/'.$id.'/duzenle');
    }
    public function edit(array $params): void { AdminAuth::requirePermission('staff.manage');$m=Admin::find((int)($params['id']??0));if(!$m){HttpError::render(404);return;}View::render('admin/staff-form',['title'=>'Ekip Üyesini Düzenle','staffMember'=>$m,'errors'=>[]]); }
    public function update(array $params): void
    {
        AdminAuth::requirePermission('staff.manage');if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$id=(int)($params['id']??0);$current=Admin::find($id);if(!$current){HttpError::render(404);return;}$data=self::input(false);$errors=Admin::validateStaffInput($data,$id,false);if(!Admin::canChangeRoleOrStatus($id,(string)$data['role'],(string)$data['status']))$errors['role']='Son aktif Süper Yönetici devre dışı bırakılamaz veya rolü düşürülemez.';if($errors!==[]){$data['id']=$id;http_response_code(422);View::render('admin/staff-form',['title'=>'Ekip Üyesini Düzenle','staffMember'=>$data,'errors'=>$errors]);return;}Admin::updateStaff($id,$data);if((string)$current['role']!==(string)$data['role'])AdminAuditLog::record((int)AdminAuth::id(),'staff.role_changed','admin',$id,['previous'=>(string)$current['role'],'role'=>(string)$data['role']]);if((string)$current['status']!==(string)$data['status'])AdminAuditLog::record((int)AdminAuth::id(),'staff.status_changed','admin',$id,['previous'=>(string)$current['status'],'status'=>(string)$data['status']]);AdminAuditLog::record((int)AdminAuth::id(),'staff.profile_updated','admin',$id,['public_slug'=>$data['public_slug'],'public_title'=>$data['public_title'],'public_profile_enabled'=>!empty($data['public_profile_enabled'])]);$_SESSION['flash_success']='Ekip üyesi güncellendi.';header('Location: /admin/ekip/'.$id.'/duzenle');
    }
    public function password(array $params): void
    {
        AdminAuth::requirePermission('staff.manage');if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$id=(int)($params['id']??0);if(!Admin::find($id)){HttpError::render(404);return;}$password=(string)($_POST['password']??'');$confirmation=(string)($_POST['password_confirmation']??'');if($password!==$confirmation||User::validatePassword($password)!==null){$_SESSION['flash_error']='Şifreler eşleşmeli; en az 10 karakter, bir harf ve bir rakam içermeli.';header('Location: /admin/ekip/'.$id.'/duzenle');return;}Admin::setStaffPassword($id,$password);AdminAuditLog::record((int)AdminAuth::id(),'staff.password_changed','admin',$id,['changed'=>true]);$_SESSION['flash_success']='Ekip üyesinin şifresi güncellendi.';header('Location: /admin/ekip/'.$id.'/duzenle');
    }
    private static function input(bool $creating): array { return ['display_name'=>(string)($_POST['display_name']??''),'username'=>(string)($_POST['username']??''),'email'=>(string)($_POST['email']??''),'role'=>(string)($_POST['role']??'writer'),'status'=>(string)($_POST['status']??'active'),'public_slug'=>(string)($_POST['public_slug']??''),'public_title'=>(string)($_POST['public_title']??''),'public_bio'=>(string)($_POST['public_bio']??''),'avatar_path'=>(string)($_POST['avatar_path']??''),'instagram_url'=>(string)($_POST['instagram_url']??''),'x_url'=>(string)($_POST['x_url']??''),'public_profile_enabled'=>isset($_POST['public_profile_enabled'])?'1':'0','password'=>$creating?(string)($_POST['password']??''):'' ]; }
    private static function defaults(): array { return ['display_name'=>'','username'=>'','email'=>'','role'=>'writer','status'=>'active','public_slug'=>'','public_title'=>'TakımBul Editörü','public_bio'=>'','avatar_path'=>'','instagram_url'=>'','x_url'=>'','public_profile_enabled'=>1]; }
}
