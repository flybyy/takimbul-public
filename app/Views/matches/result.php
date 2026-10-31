<?php
use App\Core\Csrf;
require __DIR__.'/../layouts/header.php';
$oldStats = is_array($old['stats'] ?? null) ? $old['stats'] : [];
$homeScore = $old['home_score'] ?? ($match['home_score'] ?? '');
$awayScore = $old['away_score'] ?? ($match['away_score'] ?? '');
$homePlayers = array_values(array_filter($match['players'] ?? [], fn(array $p): bool => (int)$p['team_id'] === (int)$match['home_team_id']));
$awayPlayers = array_values(array_filter($match['players'] ?? [], fn(array $p): bool => (int)$p['team_id'] === (int)$match['away_team_id']));
?>
<main class="container section-space result-entry-page">
<section class="result-entry-head panel">
    <div><span class="eyebrow">MAÇ SONRASI</span><h1>Maç Sonucunu Gir</h1><p>Skoru ve oyuncu performanslarını gir. Bu bilgiler ikinci kaptan onaylamadan kariyerlere yazılmaz.</p></div>
    <a class="btn btn-soft" href="/mac/<?= (int)$match['id'] ?>">← Maç Odası</a>
</section>
<?php if(!empty($errors['general'])): ?><div class="form-alert error"><?= htmlspecialchars((string)$errors['general']) ?></div><?php endif; ?>
<form class="result-entry-form" action="/mac/<?= (int)$match['id'] ?>/sonuc" method="post">
<input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
<section class="panel result-score-card">
    <div class="result-score-team"><span class="team-mark"><?= htmlspecialchars(strtoupper(substr((string)$match['home_team_name'],0,2))) ?></span><strong><?= htmlspecialchars((string)$match['home_team_name']) ?></strong><input type="number" inputmode="numeric" min="0" max="99" name="home_score" value="<?= htmlspecialchars((string)$homeScore) ?>" required aria-label="Ev sahibi skoru"></div>
    <span class="score-divider">:</span>
    <div class="result-score-team"><span class="team-mark alt"><?= htmlspecialchars(strtoupper(substr((string)$match['away_team_name'],0,2))) ?></span><strong><?= htmlspecialchars((string)$match['away_team_name']) ?></strong><input type="number" inputmode="numeric" min="0" max="99" name="away_score" value="<?= htmlspecialchars((string)$awayScore) ?>" required aria-label="Deplasman skoru"></div>
</section>
<div class="result-team-columns">
<?php foreach([['team'=>$match['home_team_name'],'players'=>$homePlayers],['team'=>$match['away_team_name'],'players'=>$awayPlayers]] as $group): ?>
<section class="panel result-team-panel"><div class="section-title-row"><h2><?= htmlspecialchars((string)$group['team']) ?></h2><span>Gol / Asist</span></div><div class="result-player-list">
<?php foreach($group['players'] as $player): $uid=(int)$player['user_id']; $row=is_array($oldStats[$uid]??null)?$oldStats[$uid]:$player; ?>
<div class="result-player-row"><div class="result-player-name"><span class="mini-avatar"><?= htmlspecialchars(strtoupper(substr((string)$player['display_name'],0,1))) ?></span><span><strong><?= htmlspecialchars((string)$player['display_name']) ?></strong><small><?= htmlspecialchars((string)($player['primary_position'] ?: 'Oyuncu')) ?></small></span></div><label>Gol<input type="number" inputmode="numeric" min="0" max="99" name="stats[<?= $uid ?>][goals]" value="<?= (int)($row['goals']??0) ?>"></label><label>Asist<input type="number" inputmode="numeric" min="0" max="99" name="stats[<?= $uid ?>][assists]" value="<?= (int)($row['assists']??0) ?>"></label></div>
<?php endforeach; ?>
<?php if(empty($group['players'])): ?><div class="empty-inline">Bu takım için maç kadrosu bulunamadı.</div><?php endif; ?>
</div></section>
<?php endforeach; ?>
</div>
<div class="result-submit-bar panel"><div><strong>İlk onay senden gelecek</strong><small>Gönderdiğinde rakip kaptana onay bildirimi gider.</small></div><button class="btn btn-primary" type="submit">Sonucu Onaya Gönder</button></div>
</form>
</main>
<?php require __DIR__.'/../layouts/footer.php'; ?>
