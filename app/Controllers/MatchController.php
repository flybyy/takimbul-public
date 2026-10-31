<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\FootballMatch;
use App\Models\MatchOffer;
use App\Models\MatchAttendance;
use App\Models\MatchLineup;
use App\Models\MatchRating;
use App\Models\MatchResult;
use App\Models\OpponentPost;
use App\Models\Notification;
use App\Models\RefereeMatchAssignment;
use App\Models\RefereeProfile;
use App\Models\Team;
use RuntimeException;

final class MatchController
{
    public function offerForm(array $params): void
    {
        Auth::requireLogin();
        $post = OpponentPost::find((int) ($params['id'] ?? 0));
        if ($post === null || $post['status'] !== 'open' || strtotime((string) $post['match_at']) <= time()) {
            \App\Core\HttpError::render(404,'Rakip ilanı bulunamadı veya kapanmış.');return;
        }
        $teams = array_values(array_filter(
            Team::managedTeamsForUser((int) Auth::id()),
            fn(array $team): bool => MatchOffer::canOffer((int) $post['team_id'], (int) $team['id'])
        ));
        View::render('matches/offer', ['title' => 'Maç Teklifi Gönder', 'post' => $post, 'teams' => $teams]);
    }

    public function sendOffer(array $params): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            \App\Core\HttpError::render(419);return;
        }
        $post = OpponentPost::find((int) ($params['id'] ?? 0));
        $teamId = (int) ($_POST['team_id'] ?? 0);
        $errors = [];
        if ($post === null || $post['status'] !== 'open' || strtotime((string) $post['match_at']) <= time()) {
            $errors['general'] = 'Bu ilan artık teklif almıyor.';
        } elseif (!Team::canManageSporting($teamId, (int) Auth::id())) {
            $errors['team_id'] = 'Bu takım adına teklif gönderme yetkin yok.';
        } elseif (!MatchOffer::canOffer((int) $post['team_id'], $teamId)) {
            $errors['team_id'] = 'Kendi takımının ilanına teklif gönderemezsin.';
        }

        $teams = $post ? array_values(array_filter(
            Team::managedTeamsForUser((int) Auth::id()),
            fn(array $team): bool => MatchOffer::canOffer((int) $post['team_id'], (int) $team['id'])
        )) : [];
        if ($errors !== []) {
            View::render('matches/offer', ['title' => 'Maç Teklifi Gönder', 'post' => $post, 'teams' => $teams, 'errors' => $errors, 'old' => $_POST]);
            return;
        }

        try {
            MatchOffer::create((int) $post['id'], $teamId);
            $offeringTeam = Team::find($teamId);
            Notification::create((int)$post['captain_user_id'],'match_offer','Yeni maç teklifi',($offeringTeam['name'] ?? 'Bir takım').' takımından teklif geldi.','/mac-teklifleri');
            $_SESSION['flash_success'] = 'Maç teklifin karşı takımın kaptanına gönderildi.';
            header('Location: /mac-teklifleri');
        } catch (RuntimeException $e) {
            View::render('matches/offer', ['title' => 'Maç Teklifi Gönder', 'post' => $post, 'teams' => $teams, 'errors' => ['general' => $e->getMessage()], 'old' => $_POST]);
        }
    }

    public function offers(): void
    {
        Auth::requireLogin();
        $userId = (int) Auth::id();
        View::render('matches/offers', [
            'title' => 'Maç Teklifleri',
            'incoming' => MatchOffer::incomingForCaptain($userId),
            'outgoing' => MatchOffer::outgoingForCaptain($userId),
        ]);
    }

    public function updateOffer(array $params): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            \App\Core\HttpError::render(419);return;
        }
        $status = (string) ($_POST['status'] ?? '');
        if ($status === 'accepted') {
            $result = MatchOffer::accept((int) ($params['id'] ?? 0), (int) Auth::id());
            if ($result === null) {
                $_SESSION['flash_error'] = 'Teklif kabul edilemedi veya yetkin yok.';
                header('Location: /mac-teklifleri');
                return;
            }
            Notification::create((int)$result['challenger_captain_user_id'],'match_offer','Maç teklifin kabul edildi',(string)$result['owner_team_name'].' ile maç kesinleşti.','/mac/'.(int)$result['match_id']);
            $_SESSION['flash_success'] = 'Teklif kabul edildi. Maç odası oluşturuldu.';
            header('Location: /mac/' . (int) $result['match_id']);
            return;
        }
        if ($status === 'rejected') {
            $result = MatchOffer::reject((int) ($params['id'] ?? 0), (int) Auth::id());
            if ($result) Notification::create((int)$result['challenger_captain_user_id'],'match_offer','Maç teklifin reddedildi',(string)$result['owner_team_name'].' teklifi kabul etmedi.','/mac-teklifleri');
            $_SESSION[$result ? 'flash_success' : 'flash_error'] = $result ? 'Maç teklifi reddedildi.' : 'Teklif reddedilemedi.';
            header('Location: /mac-teklifleri');
            return;
        }
        $_SESSION['flash_error'] = 'Geçersiz teklif işlemi.';
        header('Location: /mac-teklifleri');
    }

    public function withdrawOffer(array $params): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            \App\Core\HttpError::render(419);return;
        }
        $ok = MatchOffer::withdraw((int) ($params['id'] ?? 0), (int) Auth::id());
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Maç teklifin geri çekildi.' : 'Teklif geri çekilemedi.';
        header('Location: /mac-teklifleri');
    }

    public function index(): void
    {
        Auth::requireLogin();
        View::render('matches/index', ['title' => 'Maçlarım', 'matches' => FootballMatch::forUser((int) Auth::id())]);
    }

    public function show(array $params): void
    {
        Auth::requireLogin();
        $userId = (int) Auth::id();
        $match = FootballMatch::findForUser((int) ($params['id'] ?? 0), $userId);
        if ($match === null) {
            \App\Core\HttpError::render(404,'Maç bulunamadı veya bu maç odasına erişimin yok.');return;
        }
        $resultState = MatchResult::stateForMatch((int) $match['id']);
        $captainSide = FootballMatch::managerSide($match, $userId);
        $lineups = MatchLineup::forMatch((int) $match['id']);
        $ratings = MatchRating::state((int) $match['id']);
        $lineupsComplete = MatchLineup::isCompleteRows($lineups, (int)$match['home_team_id'], (int)$match['away_team_id'], (string)$match['match_format']);
        $attendance = MatchAttendance::forMatch((int)$match['id']);
        $attendanceSummary = MatchAttendance::summary($attendance,(int)$match['home_team_id'],(int)$match['away_team_id']);
        $attendanceResponse = MatchAttendance::responseForUser((int)$match['id'],$userId);
        $matchdayMode = MatchAttendance::matchdayMode((string)$match['status'],strtotime((string)$match['match_at'])?:0,time());
        View::render('matches/show', [
            'title' => 'Maç Odası',
            'match' => $match,
            'resultState' => $resultState,
            'captainSide' => $captainSide,
            'lineups' => $lineups,
            'ratings' => $ratings,
            'lineupsComplete' => $lineupsComplete,
            'attendance'=>$attendance,'attendanceSummary'=>$attendanceSummary,'attendanceResponse'=>$attendanceResponse,'matchdayMode'=>$matchdayMode,
            'canRespondAttendance'=>MatchAttendance::canRespond((string)$match['status'],strtotime((string)$match['match_at'])?:0,time()),
            'isProposer' => (int) ($resultState['result_submitted_by_user_id'] ?? 0) === $userId,
            'canSubmitResult' => $lineupsComplete && MatchResult::canSubmit((string) $match['status'], $captainSide !== null, strtotime((string) $match['match_at']) ?: PHP_INT_MAX, time()),
            'canEditLineup' => MatchLineup::canEdit((string)$match['status'], $captainSide !== null, strtotime((string)$match['match_at']) ?: 0, time()),
            'canRateMatch' => MatchRating::canRate((string)$match['status'], $captainSide !== null) && empty($match['ratings_finalized_at']),
            'refereeAssignment'=>RefereeMatchAssignment::forMatch((int)$match['id']),
            'refereeCandidates'=>$captainSide!==null?RefereeProfile::search(['province'=>(string)$match['province'],'availability_status'=>'available'],20):[],
        ]);
    }

    public function inviteReferee(array $params): void{Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}$matchId=(int)($params['id']??0);try{$id=RefereeMatchAssignment::invite($matchId,(int)Auth::id(),(int)($_POST['referee_profile_id']??0),(string)($_POST['message']??''));$assignment=RefereeMatchAssignment::forMatch($matchId);if($assignment)Notification::create((int)$assignment['user_id'],'referee_match_invite','Yeni hakem maç daveti','Bir maç için hakemlik daveti aldın.','/mac/'.$matchId);$_SESSION['flash_success']='Hakem daveti gönderildi.';}catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}header('Location: /mac/'.$matchId.'#hakem');}
    public function respondReferee(array $params): void{Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}$result=RefereeMatchAssignment::respond((int)($params['id']??0),(int)Auth::id(),(string)($_POST['decision']??''));$_SESSION[$result?'flash_success':'flash_error']=$result?'Hakem daveti yanıtlandı.':'Davet yanıtlanamadı.';header('Location: '.($result?'/mac/'.(int)$result['match_id']:'/maclar'));}

    public function attendance(array $params): void
    {
        Auth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $matchId=(int)($params['id']??0);try{$ok=MatchAttendance::setResponse($matchId,(int)Auth::id(),(string)($_POST['response']??''));$_SESSION[$ok?'flash_success':'flash_error']=$ok?'Katılım durumun güncellendi.':'Katılım durumu kaydedilemedi.';}catch(RuntimeException $e){$_SESSION['flash_error']=$e->getMessage();}header('Location: /mac/'.$matchId.'#katilim');
    }

    public function resultForm(array $params): void
    {
        Auth::requireLogin();
        $userId = (int) Auth::id();
        $match = FootballMatch::findForUser((int) ($params['id'] ?? 0), $userId);
        if ($match === null) {
            \App\Core\HttpError::render(404,'Maç bulunamadı.');return;
        }
        $captainSide = FootballMatch::managerSide($match, $userId);
        if ($captainSide === null) {
            \App\Core\HttpError::render(403,'Maç sonucunu yalnızca takım kaptanları girebilir.');return;
        }
        if (!MatchResult::canSubmit((string) $match['status'], true, strtotime((string) $match['match_at']) ?: PHP_INT_MAX, time())) {
            $_SESSION['flash_error'] = 'Bu maç için şu anda yeni sonuç girişi yapılamıyor.';
            header('Location: /mac/' . (int) $match['id']);
            return;
        }
        View::render('matches/result', [
            'title' => 'Maç Sonucunu Gir',
            'match' => MatchResult::stateForMatch((int) $match['id']),
            'captainSide' => $captainSide,
        ]);
    }

    public function submitResult(array $params): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            \App\Core\HttpError::render(419);return;
        }
        $matchId = (int) ($params['id'] ?? 0);
        $userId = (int) Auth::id();
        $match = FootballMatch::findForUser($matchId, $userId);
        if ($match === null || FootballMatch::managerSide($match, $userId) === null) {
            \App\Core\HttpError::render(403,'Bu maç için sonuç girmeye yetkin yok.');return;
        }
        try {
            $result = MatchResult::submit($matchId, $userId, [
                'home_score' => $_POST['home_score'] ?? null,
                'away_score' => $_POST['away_score'] ?? null,
                'stats' => $_POST['stats'] ?? [],
            ]);
            if ($result === null) {
                $_SESSION['flash_error'] = 'Sonuç kaydedilemedi. Maç durumu değişmiş olabilir.';
                header('Location: /mac/' . $matchId);
                return;
            }
            Notification::create((int) $result['opponent_captain_user_id'], 'match_result', 'Maç sonucu onayını bekliyor', (string) $result['home_team_name'] . ' - ' . (string) $result['away_team_name'] . ' maçı için skor ve oyuncu istatistikleri girildi.', '/mac/' . $matchId);
            $_SESSION['flash_success'] = 'Sonuç kaydedildi. Rakip kaptanın onayı bekleniyor.';
            header('Location: /mac/' . $matchId);
        } catch (RuntimeException $e) {
            View::render('matches/result', [
                'title' => 'Maç Sonucunu Gir',
                'match' => MatchResult::stateForMatch($matchId),
                'captainSide' => FootballMatch::managerSide($match, $userId),
                'errors' => ['general' => $e->getMessage()],
                'old' => $_POST,
            ]);
        }
    }

    public function confirmResult(array $params): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            \App\Core\HttpError::render(419);return;
        }
        $matchId = (int) ($params['id'] ?? 0);
        $result = MatchResult::confirm($matchId, (int) Auth::id());
        if ($result === null) {
            $_SESSION['flash_error'] = 'Bu sonucu onaylayamazsın veya sonuç artık beklemede değil.';
            header('Location: /mac/' . $matchId);
            return;
        }
        if (!empty($result['verified'])) {
            $body = (string) $result['home_team_name'] . ' - ' . (string) $result['away_team_name'] . ' maçı iki kaptan tarafından doğrulandı. Gol ve asistler kariyerlere işlendi.';
            Notification::create((int) $result['home_captain_user_id'], 'match_verified', 'Maç sonucu doğrulandı', $body, '/mac/' . $matchId);
            Notification::create((int) $result['away_captain_user_id'], 'match_verified', 'Maç sonucu doğrulandı', $body, '/mac/' . $matchId);
            $_SESSION['flash_success'] = 'Sonuç doğrulandı. Oyuncu istatistikleri kariyerlere işlendi.';
        }
        header('Location: /mac/' . $matchId);
    }

    public function disputeResult(array $params): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            \App\Core\HttpError::render(419);return;
        }
        $matchId = (int) ($params['id'] ?? 0);
        try {
            $result = MatchResult::dispute($matchId, (int) Auth::id(), (string) ($_POST['reason'] ?? ''));
            if ($result === null) {
                $_SESSION['flash_error'] = 'Bu sonuca itiraz edemezsin veya sonuç artık beklemede değil.';
                header('Location: /mac/' . $matchId);
                return;
            }
            Notification::create((int) $result['result_submitted_by_user_id'], 'match_dispute', 'Maç sonucuna itiraz edildi', (string) $result['reason'], '/mac/' . $matchId);
            $_SESSION['flash_success'] = 'İtiraz kaydedildi. Düzeltilmiş sonuç yeniden girilebilir.';
            header('Location: /mac/' . $matchId);
        } catch (RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: /mac/' . $matchId);
        }
    }
    public function lineupForm(array $params): void
    {
        Auth::requireLogin();
        $matchId=(int)($params['id'] ?? 0); $userId=(int)Auth::id();
        $state=MatchLineup::availablePlayers($matchId,$userId);
        if($state===null){ \App\Core\HttpError::render(403,'Bu maçın kadrosunu düzenleme yetkin yok.');return; }
        if(!MatchLineup::canEdit((string)$state['status'],true,strtotime((string)$state['match_at']) ?: 0,time())){ $_SESSION['flash_error']='Maç kadrosu yalnızca maç başlamadan önce düzenlenebilir.'; header('Location: /mac/'.$matchId); return; }
        View::render('matches/lineup',['title'=>'Maç Kadrosu','match'=>$state,'errors'=>[]]);
    }

    public function saveLineup(array $params): void
    {
        Auth::requireLogin();
        if(!Csrf::validate($_POST['_csrf'] ?? null)){ \App\Core\HttpError::render(419);return; }
        $matchId=(int)($params['id'] ?? 0); $userId=(int)Auth::id();
        try{
            $result=MatchLineup::save($matchId,$userId,['starters'=>$_POST['starters'] ?? [],'substitutes'=>$_POST['substitutes'] ?? []]);
            if($result===null){ $_SESSION['flash_error']='Kadro kaydedilemedi veya düzenleme süresi kapandı.'; header('Location: /mac/'.$matchId); return; }
            $_SESSION['flash_success']='Maç kadron kaydedildi. İlk '.MatchLineup::starterLimit((string)(FootballMatch::findForUser($matchId,$userId)['match_format'] ?? '7v7')).' ve yedeklerin hazır.';
            header('Location: /mac/'.$matchId);
        }catch(RuntimeException $e){
            $state=MatchLineup::availablePlayers($matchId,$userId);
            if($state===null){ http_response_code(403); return; }
            View::render('matches/lineup',['title'=>'Maç Kadrosu','match'=>$state,'errors'=>['general'=>$e->getMessage()],'old'=>$_POST]);
        }
    }

    public function performanceForm(array $params): void
    {
        Auth::requireLogin();
        $matchId=(int)($params['id'] ?? 0); $userId=(int)Auth::id();
        $match=FootballMatch::findForUser($matchId,$userId);
        if($match===null){ \App\Core\HttpError::render(404,'Maç bulunamadı.');return; }
        $side=FootballMatch::managerSide($match,$userId);
        if($side===null){ \App\Core\HttpError::render(403,'Performans puanlarını yalnızca kaptanlar girebilir.');return; }
        if(!MatchRating::canRate((string)$match['status'],true)){ $_SESSION['flash_error']='Performans puanları yalnızca doğrulanmış maçtan sonra girilebilir.'; header('Location: /mac/'.$matchId); return; }
        if(!empty($match['ratings_finalized_at'])){ $_SESSION['flash_error']='Bu maçın performans puanlaması tamamlandı ve kilitlendi.'; header('Location: /mac/'.$matchId); return; }
        $teamId=$side==='home'?(int)$match['home_team_id']:(int)$match['away_team_id'];
        $lineup=MatchLineup::forTeam($matchId,$teamId); $result=MatchResult::stateForMatch($matchId); $ratings=MatchRating::state($matchId);
        $stats=[]; foreach(($result['players'] ?? []) as $p){ $stats[(int)$p['user_id']]=$p; }
        $existing=[]; foreach($ratings as $r){ if((int)$r['team_id']===$teamId) $existing[(int)$r['user_id']]=$r; }
        View::render('matches/performance',['title'=>'Maç Performansı','match'=>$match,'side'=>$side,'lineup'=>$lineup,'stats'=>$stats,'existing'=>$existing,'errors'=>[]]);
    }

    public function submitPerformance(array $params): void
    {
        Auth::requireLogin();
        if(!Csrf::validate($_POST['_csrf'] ?? null)){ \App\Core\HttpError::render(419);return; }
        $matchId=(int)($params['id'] ?? 0); $userId=(int)Auth::id();
        try{
            $result=MatchRating::submit($matchId,$userId,is_array($_POST['scores'] ?? null)?$_POST['scores']:[]);
            if($result===null){ $_SESSION['flash_error']='Performans puanları kaydedilemedi.'; header('Location: /mac/'.$matchId); return; }
            $match=FootballMatch::findForUser($matchId,$userId);
            if(!empty($result['finalized']) && $match){
                $body='İki takımın puanları tamamlandı. Maçın MVP oyuncusu belirlendi ve sezon puanları güncellendi.';
                Notification::create((int)$match['home_captain_user_id'],'match_rating','Maç performansları tamamlandı',$body,'/mac/'.$matchId);
                Notification::create((int)$match['away_captain_user_id'],'match_rating','Maç performansları tamamlandı',$body,'/mac/'.$matchId);
                $_SESSION['flash_success']='Puanlar tamamlandı. MVP ve sezon sıralaması güncellendi.';
            }else{
                $opponent=$result['side']==='home'?(int)($match['away_captain_user_id'] ?? 0):(int)($match['home_captain_user_id'] ?? 0);
                if($opponent>0) Notification::create($opponent,'match_rating','Performans puanını gir','Rakip takım puanlamayı tamamladı. Kendi oyuncularını puanlayabilirsin.','/mac/'.$matchId.'/performans');
                $_SESSION['flash_success']='Takımının performans puanları kaydedildi. Rakip kaptanın puanlaması bekleniyor.';
            }
            header('Location: /mac/'.$matchId);
        }catch(RuntimeException $e){ $_SESSION['flash_error']=$e->getMessage(); header('Location: /mac/'.$matchId.'/performans'); }
    }

}
