<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\View;use App\Models\OpponentMatcher;use App\Models\Team;
final class RivalController
{
    public function instant(): void
    {
        Auth::requireLogin();$uid=(int)Auth::id();$teams=Team::managedTeamsForUser($uid);$teamId=(int)($_GET['team_id']??($teams[0]['id']??0));$allowed=false;foreach($teams as $t)if((int)$t['id']===$teamId)$allowed=true;if(!$allowed)$teamId=0;
        $preferences=['match_format'=>(string)($_GET['match_format']??'7v7'),'level'=>(string)($_GET['level']??'Orta'),'target_at'=>(string)($_GET['target_at']??date('Y-m-d\TH:i',time()+3*3600))];
        View::render('market/instant-rivals',['title'=>'Anlık Rakip Bul','teams'=>$teams,'teamId'=>$teamId,'preferences'=>$preferences,'candidates'=>$teamId>0?OpponentMatcher::rankedForTeam($teamId,$preferences):[]]);
    }
}
