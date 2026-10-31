<?php
$title=$title??'Bu Alana Erişimin Yok';
$message=$message??'Bu işlemi gerçekleştirmek için gerekli yetkiye sahip değilsin.';
require __DIR__.'/../layouts/header.php';
?>
<main class="container section-space error-shell"><section class="panel error-page error-card"><span class="eyebrow">403 · ERİŞİM SINIRLI</span><h1>Bu alan sana açık değil.</h1><p><?= htmlspecialchars((string)$message,ENT_QUOTES,'UTF-8') ?></p><div class="hero-actions error-actions"><a class="btn btn-primary" href="/">Ana Sayfaya Dön</a><?php if(\App\Core\Auth::check()):?><a class="btn btn-secondary" href="/profil/duzenle">Profilime Git</a><?php else:?><a class="btn btn-secondary" href="/giris">Giriş Yap</a><?php endif;?></div></section></main>
<?php require __DIR__.'/../layouts/footer.php'; ?>
