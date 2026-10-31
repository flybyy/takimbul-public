<?php use App\Core\TurkeyLocation; use App\Support\VerificationBadge; require __DIR__.'/../layouts/header.php'; ?>
<main class="container section-space leaderboard-page">
<section class="leaderboard-hero panel">
    <div><span class="eyebrow">TAKIMBUL PERFORMANS ENDEKSİ</span><h1>Sezon Sıralaması</h1><p>Yalnızca doğrulanmış maçlar ve iki takımın tamamladığı performans puanları sıralamaya girer.</p></div>
    <div class="leaderboard-shield"><strong><?= count($rows) ?></strong><span>oyuncu</span></div>
</section>
<form class="leaderboard-filters panel" method="get" action="/siralama">
    <label>Sezon<select name="season"><?php foreach($seasons as $season): ?><option value="<?= (int)$season['id'] ?>" <?= (int)$season['id']===$seasonId?'selected':'' ?>><?= htmlspecialchars((string)$season['name']) ?></option><?php endforeach; ?></select></label>
    <label>İl<select name="province" data-location-province><?= TurkeyLocation::provinceOptions((string)$province,'Türkiye geneli') ?></select></label>
    <label>İlçe<select name="district" data-location-district data-empty-label="Tüm ilçeler"><?= TurkeyLocation::districtOptions((string)$province,(string)$district,'Tüm ilçeler') ?></select></label>
    <label>Sırala<select name="sort"><option value="rating" <?= $sort==='rating'?'selected':'' ?>>Genel Puan</option><option value="goals" <?= $sort==='goals'?'selected':'' ?>>Gol</option><option value="assists" <?= $sort==='assists'?'selected':'' ?>>Asist</option><option value="mvps" <?= $sort==='mvps'?'selected':'' ?>>MVP</option><option value="matches" <?= $sort==='matches'?'selected':'' ?>>Maç</option></select></label>
    <button class="btn btn-primary" type="submit">Filtrele</button>
</form>
<section class="leaderboard-list panel">
    <div class="leaderboard-head"><span>#</span><span>Oyuncu</span><span>Puan</span><span>Maç</span><span>Gol</span><span>Asist</span><span>MVP</span></div>
    <?php foreach($rows as $i=>$row): ?>
    <a class="leaderboard-row" href="/kariyer/<?= (int)$row['user_id'] ?>">
        <b class="rank-no <?= $i<3?'top-rank':'' ?>"><?= $i+1 ?></b>
        <span class="leader-player"><i><?= htmlspecialchars(strtoupper(substr((string)$row['display_name'],0,1))) ?></i><span><strong><?= htmlspecialchars((string)$row['display_name']) ?><?= VerificationBadge::render($row) ?></strong><small><?= htmlspecialchars((string)$row['district']) ?>, <?= htmlspecialchars((string)$row['province']) ?></small></span></span>
        <strong class="rating-pill"><?= number_format((float)$row['rating'],1,',','.') ?></strong>
        <span><?= (int)$row['matches'] ?></span><span><?= (int)$row['goals'] ?></span><span><?= (int)$row['assists'] ?></span><span><?= (int)$row['mvps'] ?></span>
    </a>
    <?php endforeach; ?>
    <?php if(empty($rows)): ?><div class="empty-state"><strong>Bu filtrede sıralama henüz oluşmadı.</strong><p>Doğrulanmış maçlar ve tamamlanan performans puanları geldikçe liste otomatik oluşur.</p></div><?php endif; ?>
</section>
</main>
<?php require __DIR__.'/../layouts/footer.php'; ?>
