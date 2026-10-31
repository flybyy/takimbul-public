<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container section-space">
    <div class="page-heading split"><div><span class="eyebrow">TÜRKİYE'NİN TAKIMLARI</span><h1>Sahaya çıkacak ekibini bul.</h1></div><a class="btn btn-primary" href="/takim-olustur">Takım Oluştur</a></div>
    <div class="card-grid team-grid">
        <?php foreach(($teams ?? []) as $team): ?>
            <a class="team-card panel" href="/takim/<?= (int)$team['id'] ?>">
                <div class="team-badge"><?= htmlspecialchars(strtoupper(substr((string)$team['name'], 0, 2))) ?></div>
                <div><h3><?= htmlspecialchars((string)$team['name']) ?></h3><p><?= htmlspecialchars((string)$team['district']) ?>, <?= htmlspecialchars((string)$team['province']) ?></p></div>
                <span class="card-arrow">→</span>
            </a>
        <?php endforeach; ?>
        <?php if(empty($teams)): ?><div class="empty-state panel"><strong>Henüz takım yok.</strong><p>İlk takımı sen kur.</p><a class="btn btn-primary" href="/takim-olustur">Takım Oluştur</a></div><?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
