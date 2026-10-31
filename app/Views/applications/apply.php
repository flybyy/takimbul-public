<?php require __DIR__ . '/../layouts/header.php'; $errors=$errors ?? []; $old=$old ?? []; ?>
<main class="container section-space compact-page">
    <div class="form-page-head"><div><span class="eyebrow">OYUNCU BAŞVURUSU</span><h1><?= htmlspecialchars((string)$post['position']) ?> ilanına başvur</h1><p><?= htmlspecialchars((string)$post['team_name']) ?> · <?= htmlspecialchars((string)$post['district']) ?>, <?= htmlspecialchars((string)$post['province']) ?></p></div><a class="text-link" href="/oyuncu-bul">← İlanlara dön</a></div>
    <div class="panel action-detail-card">
        <div class="action-detail-grid"><div><small>Maç</small><strong><?= date('d.m.Y · H:i', strtotime((string)$post['match_at'])) ?></strong></div><div><small>Aranan mevki</small><strong><?= htmlspecialchars((string)$post['position']) ?></strong></div><div><small>Seviye</small><strong><?= htmlspecialchars((string)$post['level']) ?></strong></div><div><small>Konum</small><strong><?= htmlspecialchars((string)$post['venue'] ?: $post['district']) ?></strong></div></div>
        <?php if(isset($errors['general'])): ?><div class="form-error wide-error"><?= htmlspecialchars((string)$errors['general']) ?></div><?php endif; ?>
        <form method="post" class="stack-form action-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
            <label><span>Kaptana kısa mesaj <small>(isteğe bağlı)</small></span><textarea name="message" maxlength="300" rows="5" placeholder="Pozisyonun, uygunluğun veya kısa notun..."><?= htmlspecialchars((string)($old['message'] ?? '')) ?></textarea><?php if(isset($errors['message'])): ?><em><?= htmlspecialchars((string)$errors['message']) ?></em><?php endif; ?></label>
            <button class="btn btn-primary btn-wide" type="submit">Başvuruyu Gönder</button>
        </form>
    </div>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
