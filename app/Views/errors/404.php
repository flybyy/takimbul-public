<?php
$title=$title??'Sayfa Bulunamadı';
$message=$message??'Aradığın bağlantı kaldırılmış, taşınmış veya hiç oluşmamış olabilir.';
require __DIR__.'/../layouts/header.php';
?>
<main class="container section-space error-shell"><section class="panel error-page error-card"><span class="eyebrow">404 · BULUNAMADI</span><h1>Bu sayfa sahada değil.</h1><p><?= htmlspecialchars((string)$message,ENT_QUOTES,'UTF-8') ?></p><div class="hero-actions error-actions"><a class="btn btn-primary" href="/">Ana Sayfaya Dön</a><a class="btn btn-secondary" href="/rakip-bul/anlik">Rakip Bul</a></div></section></main>
<?php require __DIR__.'/../layouts/footer.php'; ?>
