<?php
$title=$title??'Çok fazla istek gönderildi';
$message=$message??'Kısa sürede çok fazla işlem yaptın. Güvenlik sınırı sıfırlandığında aynı işlemi yeniden deneyebilirsin.';
require __DIR__.'/../layouts/header.php';
?>
<main class="container section-space error-shell"><section class="panel error-page error-card"><span class="eyebrow">429 · İSTEK SINIRI</span><h1>Biraz fazla hızlı gidiyoruz.</h1><p><?= htmlspecialchars((string)$message,ENT_QUOTES,'UTF-8') ?></p><div class="hero-actions error-actions"><a class="btn btn-primary" href="<?= htmlspecialchars((string)($_SERVER['HTTP_REFERER']??'/'),ENT_QUOTES,'UTF-8') ?>">Tekrar Dene</a><a class="btn btn-secondary" href="/">Ana Sayfaya Dön</a></div><small class="error-help">Bu sınır hesabını ve platformu otomatik denemelere karşı korur.</small></section></main>
<?php require __DIR__.'/../layouts/footer.php'; ?>
