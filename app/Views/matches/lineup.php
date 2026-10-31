<?php
use App\Core\Csrf;
use App\Models\MatchLineup;
require __DIR__.'/../layouts/header.php';
$limit=MatchLineup::starterLimit((string)$match['match_format']);
$selectedStarters=[]; $selectedSubs=[];
foreach(($match['lineup'] ?? []) as $row){ if(($row['role']??'')==='starter') $selectedStarters[]=(int)$row['user_id']; else $selectedSubs[]=(int)$row['user_id']; }
if(isset($old) && is_array($old)){
    $selectedStarters=array_map('intval',is_array($old['starters']??null)?$old['starters']:[]);
    $selectedSubs=array_map('intval',is_array($old['substitutes']??null)?$old['substitutes']:[]);
}
$teamName=$match['side']==='home'?$match['home_team_name']:$match['away_team_name'];
?>
<main class="container section-space lineup-page">
<section class="lineup-head panel">
    <div><span class="eyebrow">MAÇ KADROSU</span><h1><?= htmlspecialchars((string)$teamName) ?></h1><p><?= htmlspecialchars((string)$match['match_format']) ?> maç için tam <strong>İlk <?= $limit ?></strong> seç. İstersen en fazla 7 yedek ekle. Kadro maç saatinde otomatik kilitlenir.</p></div>
    <a class="btn btn-soft" href="/mac/<?= (int)$match['id'] ?>">← Maç Odası</a>
</section>
<?php if(!empty($errors['general'])): ?><div class="form-alert error"><?= htmlspecialchars((string)$errors['general']) ?></div><?php endif; ?>
<form class="lineup-form" data-lineup-selector data-starter-limit="<?= $limit ?>" action="/mac/<?= (int)$match['id'] ?>/kadro" method="post">
<input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
<div class="lineup-counter panel"><div><strong><span data-starter-count><?= count($selectedStarters) ?></span>/<?= $limit ?></strong><small>İlk <?= $limit ?></small></div><div><strong><span data-sub-count><?= count($selectedSubs) ?></span>/7</strong><small>Yedek</small></div><span>Maç: <?= date('d.m.Y H:i',strtotime((string)$match['match_at'])) ?></span></div>
<section class="panel lineup-list-panel">
    <div class="section-title-row"><div><span class="eyebrow">AKTİF TAKIM KADROSU</span><h2>İlk Kadro & Yedekler</h2></div><span><?= count($match['available_players'] ?? []) ?> oyuncu</span></div>
    <div class="lineup-player-list">
    <?php foreach(($match['available_players'] ?? []) as $player): $uid=(int)$player['user_id']; ?>
        <article class="lineup-player-card" data-player-id="<?= $uid ?>">
            <div class="lineup-player-info"><span class="mini-avatar"><?= htmlspecialchars(strtoupper(substr((string)$player['display_name'],0,1))) ?></span><span><strong><?= htmlspecialchars((string)$player['display_name']) ?></strong><small><?= htmlspecialchars((string)($player['primary_position'] ?: 'Oyuncu')) ?> · <?= htmlspecialchars((string)$player['team_role']) ?></small></span></div>
            <div class="lineup-choice">
                <label><input type="checkbox" data-role="starter" name="starters[]" value="<?= $uid ?>" <?= in_array($uid,$selectedStarters,true)?'checked':'' ?>><span>İlk <?= $limit ?></span></label>
                <label><input type="checkbox" data-role="substitute" name="substitutes[]" value="<?= $uid ?>" <?= in_array($uid,$selectedSubs,true)?'checked':'' ?>><span>Yedek</span></label>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if(empty($match['available_players'])): ?><div class="empty-state"><strong>Aktif takım oyuncusu bulunamadı.</strong><p>Önce takım kadrosuna oyuncu eklemelisin.</p></div><?php endif; ?>
    </div>
</section>
<div class="lineup-save-bar panel"><div><strong>İlk <?= $limit ?> + yedekler</strong><small>Kaydettiğinde maç sonucu ekranı yalnızca bu oyuncuları kullanır.</small></div><button class="btn btn-primary" type="submit">Kadroyu Kaydet</button></div>
</form>
</main>
<?php require __DIR__.'/../layouts/footer.php'; ?>
