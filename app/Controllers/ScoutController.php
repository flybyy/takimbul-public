<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\ScoutAccount;
use App\Models\User;
use App\Models\ScoutWatchlist;
use App\Models\Notification;
use RuntimeException;

final class ScoutController
{
    public function index(): void
    {
        $account = Auth::id() ? ScoutAccount::forUser((int)Auth::id()) : null;
        $approved = Auth::id() ? ScoutAccount::canDiscover((int)Auth::id()) : false;
        $players = [];
        if ($approved) {
            $players = User::publicPlayers($_GET, 30);
        }
        View::render('scout/index', ['title'=>'Kulüpler & Scoutlar','account'=>$account,'approved'=>$approved,'players'=>$players,'filters'=>$_GET]);
    }

    public function applyForm(): void
    {
        Auth::requireLogin();
        View::render('scout/apply', ['title'=>'Scout / Kulüp Başvurusu','account'=>ScoutAccount::forUser((int)Auth::id())]);
    }

    public function apply(): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) { \App\Core\HttpError::render(419);return; }
        $errors=ScoutAccount::validateApplication($_POST);
        if ($errors) { View::render('scout/apply',['title'=>'Scout / Kulüp Başvurusu','errors'=>$errors,'old'=>$_POST]); return; }
        ScoutAccount::apply((int)Auth::id(), $_POST);
        $_SESSION['flash_success']='Başvurun alındı. Yönetici incelemesinden sonra doğrulanmış hesap açılacak.';
        header('Location: /scout');
    }
    public function watchlist(): void
    {
        Auth::requireLogin();
        if(!ScoutAccount::canDiscover((int)Auth::id())){\App\Core\HttpError::render(403,'Takip listesi yalnızca doğrulanmış scout/kulüp hesaplarına açıktır.');return;}
        View::render('scout/watchlist',['title'=>'Takip Listem','players'=>ScoutWatchlist::forScoutUser((int)Auth::id())]);
    }

    public function toggleWatch(array $params): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $playerId=(int)($params['id']??0);
        try{$added=ScoutWatchlist::toggle((int)Auth::id(),$playerId);$_SESSION['flash_success']=$added?'Oyuncu takip listene eklendi.':'Oyuncu takip listenden çıkarıldı.';}
        catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}
        $returnTo=Notification::safeLink(isset($_POST['return_to'])?(string)$_POST['return_to']:null) ?? '/scout/takip-listem';
        header('Location: '.$returnTo);
    }

}
