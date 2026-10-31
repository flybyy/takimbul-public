<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\View;
use App\Models\OpponentPost;
use App\Models\PlayerWantedPost;
use App\Models\Team;

final class MarketController
{
    public function players(): void
    {
        $loadError = false;
        try {
            $posts = PlayerWantedPost::search($_GET);
        } catch (\Throwable $e) {
            Logger::exception($e);
            $posts = [];
            $loadError = true;
        }
        View::render('market/players', ['title'=>'Oyuncu Bul','posts'=>$posts,'filters'=>$_GET,'loadError'=>$loadError]);
    }

    public function opponents(): void
    {
        $loadError = false;
        try {
            $posts = OpponentPost::search($_GET);
        } catch (\Throwable $e) {
            Logger::exception($e);
            $posts = [];
            $loadError = true;
        }
        View::render('market/opponents', ['title'=>'Rakip Bul','posts'=>$posts,'filters'=>$_GET,'loadError'=>$loadError]);
    }
    public function createPlayer(): void { Auth::requireLogin(); View::render('market/create-player',['title'=>'Oyuncu İlanı Aç','teams'=>Team::managedTeamsForUser((int)Auth::id()),'old'=>['team_id'=>$_GET['team'] ?? '']]); }
    public function createOpponent(): void { Auth::requireLogin(); View::render('market/create-opponent',['title'=>'Rakip İlanı Aç','teams'=>Team::managedTeamsForUser((int)Auth::id()),'old'=>['team_id'=>$_GET['team'] ?? '']]); }

    public function storePlayer(): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) { \App\Core\HttpError::render(419);return; }
        $teamId=(int)($_POST['team_id'] ?? 0); $errors=PlayerWantedPost::validateInput($_POST);
        if (!Team::canManageSporting($teamId,(int)Auth::id())) $errors['team_id']='Bu takım adına ilan açma yetkin yok.';
        if ($errors) { View::render('market/create-player',['title'=>'Oyuncu İlanı Aç','teams'=>Team::managedTeamsForUser((int)Auth::id()),'errors'=>$errors,'old'=>$_POST]); return; }
        PlayerWantedPost::create((int)Auth::id(),$_POST); $_SESSION['flash_success']='Oyuncu ilanı yayınlandı.'; header('Location: /oyuncu-bul');
    }

    public function storeOpponent(): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) { \App\Core\HttpError::render(419);return; }
        $teamId=(int)($_POST['team_id'] ?? 0); $errors=OpponentPost::validateInput($_POST);
        if (!Team::canManageSporting($teamId,(int)Auth::id())) $errors['team_id']='Bu takım adına ilan açma yetkin yok.';
        if ($errors) { View::render('market/create-opponent',['title'=>'Rakip İlanı Aç','teams'=>Team::managedTeamsForUser((int)Auth::id()),'errors'=>$errors,'old'=>$_POST]); return; }
        OpponentPost::create((int)Auth::id(),$_POST); $_SESSION['flash_success']='Rakip ilanı yayınlandı.'; header('Location: /rakip-bul');
    }
}
