<?php require __DIR__ . '/../layouts/header.php';
$statusLabels=['pending'=>'Bekliyor','accepted'=>'Kabul Edildi','rejected'=>'Reddedildi','withdrawn'=>'Geri Çekildi'];
$statusClasses=['pending'=>'status-waiting','accepted'=>'status-open','rejected'=>'status-danger','withdrawn'=>'status-muted'];
?>
<main class="container section-space social-page">
    <div class="social-page-head"><div><span class="eyebrow">KADRO YÖNETİMİ</span><h1>Oyuncu başvuruları</h1><p>Gönderdiğin başvuruları ve kaptanı olduğun takımlara gelen oyuncu taleplerini tek yerde yönet.</p></div><a class="btn btn-primary" href="/oyuncu-bul">Oyuncu İlanlarını Gör</a></div>

    <section class="social-section"><div class="section-title-row"><h2>Takımlarıma Gelenler</h2><span><?= count($incoming ?? []) ?> başvuru</span></div>
        <div class="social-card-list">
        <?php foreach(($incoming ?? []) as $item): ?><article class="panel social-action-card">
            <div class="social-card-main"><div class="avatar-letter"><?= htmlspecialchars(strtoupper(substr((string)$item['applicant_name'],0,1))) ?></div><div><div class="social-card-title"><strong><?= htmlspecialchars((string)$item['applicant_name']) ?></strong><span class="<?= $statusClasses[$item['status']] ?? 'status-muted' ?>"><?= htmlspecialchars($statusLabels[$item['status']] ?? $item['status']) ?></span></div><p><?= htmlspecialchars((string)($item['applicant_position'] ?: 'Mevki belirtilmedi')) ?> · <?= htmlspecialchars((string)$item['applicant_district']) ?>, <?= htmlspecialchars((string)$item['applicant_province']) ?></p><small><?= htmlspecialchars((string)$item['team_name']) ?> · <?= date('d.m · H:i',strtotime((string)$item['match_at'])) ?> · <?= htmlspecialchars((string)$item['position']) ?> aranıyor</small><?php if($item['message']): ?><blockquote>“<?= htmlspecialchars((string)$item['message']) ?>”</blockquote><?php endif; ?></div></div>
            <div class="social-card-actions"><?php if($item['status']==='pending'): ?><form method="post" action="/basvurular/<?= (int)$item['id'] ?>/durum"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>"><input type="hidden" name="status" value="accepted"><button class="btn btn-primary btn-small">Kabul Et</button></form><form method="post" action="/basvurular/<?= (int)$item['id'] ?>/durum"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>"><input type="hidden" name="status" value="rejected"><button class="btn btn-soft btn-small">Reddet</button></form><?php endif; ?><a class="btn btn-ghost btn-small" href="/mesaj/yeni/<?= (int)$item['user_id'] ?>">Mesaj Gönder</a></div>
        </article><?php endforeach; ?>
        <?php if(empty($incoming)): ?><div class="empty-state panel"><strong>Henüz gelen oyuncu başvurusu yok.</strong><p>Açık ilanlarına yapılan başvurular burada görünecek.</p></div><?php endif; ?>
        </div>
    </section>

    <section class="social-section"><div class="section-title-row"><h2>Başvurularım</h2><span><?= count($outgoing ?? []) ?> başvuru</span></div>
        <div class="social-card-list compact-list">
        <?php foreach(($outgoing ?? []) as $item): ?><article class="panel social-action-card"><div class="social-card-main"><div class="avatar-letter team-letter">⚽</div><div><div class="social-card-title"><strong><?= htmlspecialchars((string)$item['team_name']) ?></strong><span class="<?= $statusClasses[$item['status']] ?? 'status-muted' ?>"><?= htmlspecialchars($statusLabels[$item['status']] ?? $item['status']) ?></span></div><p><?= htmlspecialchars((string)$item['position']) ?> · <?= date('d.m.Y · H:i',strtotime((string)$item['match_at'])) ?></p><small><?= htmlspecialchars((string)$item['district']) ?>, <?= htmlspecialchars((string)$item['province']) ?><?= $item['venue']?' · '.htmlspecialchars((string)$item['venue']):'' ?></small></div></div><?php if($item['status']==='pending'): ?><div class="social-card-actions"><form method="post" action="/basvurular/<?= (int)$item['id'] ?>/geri-cek"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>"><button class="btn btn-ghost btn-small">Geri Çek</button></form></div><?php endif; ?></article><?php endforeach; ?>
        <?php if(empty($outgoing)): ?><div class="empty-state panel"><strong>Henüz bir ilana başvurmadın.</strong><p>Konumuna uygun eksik oyuncu ilanlarını keşfet.</p></div><?php endif; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
