<?php use App\Core\Icon; require __DIR__.'/../layouts/header.php'; $completion=(int)($completion??0); $checks=$checks??[]; ?>
<main class="container section-space v104-onboarding">
<section class="v104-onboarding-hero panel">
<div><span class="eyebrow">HOŞ GELDİN</span><h1>Burada ne yapmak istiyorsun?</h1><p>TakımBul'u amacına göre kullan. İstersen kendini tanıt, takım ara, eksik oyuncu bul veya rakip eşleştir.</p></div>
<div class="v104-completion"><strong>%<?= $completion ?></strong><span>Profil tamamlandı</span><div><i style="width:<?= $completion ?>%"></i></div></div>
</section>
<section class="v104-onboarding-grid">
<a class="panel" href="/amator-ilanim"><span>01</span><h2>Kendimi Tanıt</h2><p>Kariyerin, geçmiş kulüplerin, istatistiklerin ve videonla oyuncu vitrini oluştur.</p><?= Icon::svg('arrow') ?></a>
<a class="panel" href="/takim-ariyorum"><span>02</span><h2>Takım Arıyorum</h2><p>Mevki ve konum bilgilerinle takım ihtiyaçlarıyla eşleş.</p><?= Icon::svg('arrow') ?></a>
<a class="panel" href="/oyuncu-bul"><span>03</span><h2>Oyuncu Arıyorum</h2><p>Takımındaki eksik mevki için açık oyuncu ilanlarını yönet.</p><?= Icon::svg('arrow') ?></a>
<a class="panel" href="/rakip-bul/anlik"><span>04</span><h2>Rakip Bul</h2><p>Takımına uygun açık rakipleri hızlıca eşleştir.</p><?= Icon::svg('arrow') ?></a>
</section>
<section class="panel v104-profile-next"><div><span class="eyebrow">SONRAKİ ADIM</span><h2>Profilini biraz daha güçlendir.</h2><p>Fotoğraf, mevki ve kısa futbol özeti eklemek oyuncu kartlarının daha güvenilir görünmesini sağlar.</p></div><a class="btn btn-primary" href="/profil/duzenle">Profili Tamamla</a></section>
</main>
<?php require __DIR__.'/../layouts/footer.php'; ?>
