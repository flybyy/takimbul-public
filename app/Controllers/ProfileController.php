<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use App\Models\PlayerProfile;
use App\Models\User;
use Throwable;

final class ProfileController
{
    public function edit(): void
    {
        Auth::requireLogin();
        $id=(int)Auth::id();
        View::render('profile/edit', [
            'title' => 'Futbol Profilim',
            'identity' => User::profileIdentity($id) ?? [],
            'profile' => PlayerProfile::find($id) ?? [],
        ]);
    }

    public function update(): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) { \App\Core\HttpError::render(419,'Oturum doğrulaması başarısız.');return; }
        $id=(int)Auth::id();
        $identityErrors=User::validateProfileIdentity($_POST,$id);
        $profileErrors=PlayerProfile::validateInput($_POST);
        $avatarError=PlayerProfile::validateAvatarFile($_FILES['avatar'] ?? ['error'=>UPLOAD_ERR_NO_FILE]);
        $errors=$identityErrors+$profileErrors;
        if($avatarError!==null)$errors['avatar']=$avatarError;
        $currentProfile=PlayerProfile::find($id) ?? [];
        if($errors!==[]){
            View::render('profile/edit',['title'=>'Futbol Profilim','identity'=>$_POST,'profile'=>array_merge($currentProfile,$_POST),'errors'=>$errors]);return;
        }

        $oldAvatar=(string)($currentProfile['avatar_path'] ?? '');
        $newAvatar=$oldAvatar;
        try {
            $newAvatar=PlayerProfile::storeAvatar($id,$_FILES['avatar'] ?? ['error'=>UPLOAD_ERR_NO_FILE],$oldAvatar);
            $pdo=Database::connection();$pdo->beginTransaction();
            User::updateProfileIdentity($id,$_POST);
            $profileData=$_POST;$profileData['avatar_path']=$newAvatar;
            PlayerProfile::upsert($id,$profileData);
            $pdo->commit();
            if($newAvatar!==$oldAvatar && $oldAvatar!=='')PlayerProfile::deleteAvatar($oldAvatar);
            if(isset($_SESSION['user']) && is_array($_SESSION['user'])) $_SESSION['user']['name']=trim((string)$_POST['display_name']);
            $_SESSION['flash_success']='Profilin ve futbol bilgilerin güncellendi.';
            header('Location: /profil/duzenle');
        } catch(Throwable $e){
            try { $pdo=Database::connection(); if($pdo->inTransaction())$pdo->rollBack(); } catch(Throwable) {}
            if($newAvatar!==$oldAvatar)PlayerProfile::deleteAvatar($newAvatar);
            http_response_code(422);
            View::render('profile/edit',['title'=>'Futbol Profilim','identity'=>$_POST,'profile'=>array_merge($currentProfile,$_POST),'errors'=>['profile'=>'Profil kaydedilemedi. Lütfen tekrar dene.']]);
        }
    }
}
