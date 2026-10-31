<?php
use App\Core\Csrf;
require __DIR__.'/../layouts/header.php';
$teamName=$side==='home'?$match['home_team_name']:$match['away_team_name'];
?>
<main class="container section-space performance-page">
<section class="performance-head panel"><div><span class="eyebrow">MAÇ SONRASI PERFORMANS</span><h1>Oyuncu Maç Puanı</h1><p><?= htmlspecialchars((string)$teamName) ?> oyuncularını 1.0–10.0 arasında değerlendir. Nihai puan kaptan değerlendirmesi ile doğrulanmış gol/asist katkısını birleştirir.</p></div><a class="btn btn-soft" href="/mac/<?= (int)$match['id'] ?>">← Maç Odası</a></section>
<?php if(!empty($errors['general'])): ?><div class="form-alert error"><?= htmlspecialchars((string)$errors['general']) ?></div><?php endif; ?>
<form class="performance-form" action="/mac/<?= (int)$match['id'] ?>/performans" method="post">
<input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
<section class="panel rating-list-panel">
<div class="section-title-row"><div><span class="eyebrow">TAKIM DEĞERLENDİRMESİ</span><h2><?= htmlspecialchars((string)$teamName) ?></h2></div><span><?= count($lineup) ?> oyuncu</span></div>
<div class="rating-player-list">
<?php foreach($lineup as $player): $uid=(int)$player['user_id']; $stat=$stats[$uid]??[]; $prev=$existing[$uid]??[]; $value=$prev['captain_score']??'6.5'; ?>
<article class="rating-player-row">
    <div class="rating-player-info"><span class="mini-avatar"><?= htmlspecialchars(strtoupper(substr((string)$player['display_name'],0,1))) ?></span><span><strong><?= htmlspecialchars((string)$player['display_name']) ?></strong><small><?= ($player['role']??'')==='starter'?'İlk kadro':'Yedek' ?> · <?= (int)($stat['goals']??0) ?> gol · <?= (int)($stat['assists']??0) ?> asist</small></span></div>
    <label class="rating-input"><span>Maç Puanı</span><input type="number" inputmode="decimal" min="1" max="10" step="0.1" name="scores[<?= $uid ?>]" value="<?= htmlspecialchars((string)$value) ?>" required></label>
</article>
<?php endforeach; ?>
<?php if(empty($lineup)): ?><div class="empty-state"><strong>Maç kadrosu bulunamadı.</strong><p>Bu maç için performans puanı oluşturulamıyor.</p></div><?php endif; ?>
</div>
</section>
<div class="rating-form-note panel"><div><strong>Puan nasıl oluşur?</strong><small>%65 kaptan puanı + %35 doğrulanmış performans katkısı. İki kaptan kendi takımlarını tamamlayınca MVP otomatik seçilir.</small></div><button class="btn btn-primary" type="submit">Puanları Kaydet</button></div>
</form>
</main>
<?php require __DIR__.'/../layouts/footer.php'; ?>
