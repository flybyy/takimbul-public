<?php
$title=$title??'Oturum Yenilenmeli';
$message=$message??'Güvenlik nedeniyle bu formun süresi doldu. Sayfayı yenileyip işlemi tekrar deneyebilirsin.';
require __DIR__.'/../layouts/header.php';
?>
<main class="container section-space error-shell"><section class="panel error-page error-card"><span class="eyebrow">419 · OTURUM DOĞRULAMASI</span><h1>Formun süresi doldu.</h1><p><?= htmlspecialchars((string)$message,ENT_QUOTES,'UTF-8') ?></p><div class="hero-actions error-actions"><a class="btn btn-primary" href="<?= htmlspecialchars((string)($_SERVER['HTTP_REFERER']??'/'),ENT_QUOTES,'UTF-8') ?>">Tekrar Dene</a><a class="btn btn-secondary" href="/">Ana Sayfaya Dön</a></div></section></main>
<?php require __DIR__.'/../layouts/footer.php'; ?>
