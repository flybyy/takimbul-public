<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Models\AmateurPlayerAd;use App\Core\Database;use App\Core\View;use App\Models\Career;use App\Models\FootballMatch;use App\Models\OpponentPost;use App\Models\PlayerAvailability;use App\Models\PlayerProfile;use App\Models\PlayerWantedPost;use App\Models\News;use App\Models\SiteSetting;use App\Models\Team;use App\Models\TrustScore;use App\Models\User;use Throwable;
final class HomeController
{
    public function index(): void
    {
        $data=['title'=>'Türkiye’nin Futbol Ağı','counts'=>['players'=>0,'teams'=>0,'open_posts'=>0,'verified_matches'=>0],'playerPosts'=>[],'opponentPosts'=>[],'players'=>[],'teams'=>[],'currentUser'=>null,'currentProfile'=>null,'currentStats'=>['matches'=>0,'goals'=>0,'assists'=>0,'season_rating'=>0.0,'season_mvps'=>0,'season_matches'=>0,'season_name'=>'Aktif Sezon'],'trust'=>['score'=>0],'upcomingMatches'=>[],'currentAvailability'=>null,'nearbyAvailable'=>[],'myTeams'=>[],'managedTeams'=>[],'adContext'=>[],'siteSettings'=>SiteSetting::all(),'amateurPlayers'=>[],'latestNews'=>[]];
        try{
            $pdo=Database::connection();$data['counts']=['players'=>(int)$pdo->query('SELECT COUNT(*) FROM users u INNER JOIN player_profiles p ON p.user_id=u.id WHERE u.status="active" AND p.visibility="public"')->fetchColumn(),'teams'=>(int)$pdo->query('SELECT COUNT(*) FROM teams WHERE status="active"')->fetchColumn(),'open_posts'=>(int)$pdo->query('SELECT (SELECT COUNT(*) FROM opponent_posts WHERE status="open" AND match_at>=NOW()) + (SELECT COUNT(*) FROM player_wanted_posts WHERE status="open" AND match_at>=NOW())')->fetchColumn(),'verified_matches'=>(int)$pdo->query('SELECT COUNT(*) FROM matches WHERE status="verified"')->fetchColumn()];
            $data['playerPosts']=PlayerWantedPost::search([],4);try{$data['latestNews']=News::featuredOrLatest(3);}catch(Throwable){$data['latestNews']=[];}$data['opponentPosts']=OpponentPost::search([],4);$data['players']=User::publicPlayers([],4);$data['teams']=Team::recent(4);$data['amateurPlayers']=AmateurPlayerAd::recentActive(4);
            $authId=Auth::id();if($authId!==null){$id=(int)$authId;$data['currentUser']=User::profileIdentity($id);$data['currentProfile']=PlayerProfile::find($id);$data['currentStats']=array_merge($data['currentStats'],Career::summary($id));$data['trust']=TrustScore::forUser($id);$data['upcomingMatches']=FootballMatch::upcomingForUser($id,4);$data['currentAvailability']=PlayerAvailability::activeForUser($id);$data['myTeams']=Team::forUser($id);$data['managedTeams']=Team::managedTeamsForUser($id);if(is_array($data['currentUser'])){$data['adContext']=['province'=>(string)($data['currentUser']['province']??''),'district'=>(string)($data['currentUser']['district']??'')];$data['nearbyAvailable']=PlayerAvailability::browse(['province'=>(string)($data['currentUser']['province']??''),'district'=>(string)($data['currentUser']['district']??'')],4);}}
        }catch(Throwable){}
        View::render('home',$data);
    }
}
