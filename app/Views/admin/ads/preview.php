<?php use App\Core\TurkeyLocation;
use App\Core\AdRenderer;
require __DIR__.'/../../layouts/admin-header.php';
$diag=$diagnosis??['code'=>'unknown','label'=>'Bilinmiyor','detail'=>'','ok'=>false];
?>
<main class="container section-space ad-preview-page">
    <div class="section-head"><div><span class="eyebrow">REKLAM MERKEZİ</span><h1>Reklam Önizleme</h1><p>Bu ekran gösterim sayacını artırmaz; yalnızca kartın gerçek tasarımını ve teslimat durumunu gösterir.</p></div><a class="btn btn-ghost" href="/admin/reklamlar">← Reklamlar</a></div>
    <section class="panel ad-diagnostic-panel <?= !empty($diag['ok'])?'is-ok':'is-warning' ?>"><div><small>TESLİMAT DURUMU</small><strong><?= htmlspecialchars((string)$diag['label']) ?></strong><p><?= htmlspecialchars((string)$diag['detail']) ?></p></div><dl><div><dt>Slot</dt><dd><?= htmlspecialchars((string)($context['placement']??'')) ?></dd></div><div><dt>Cihaz</dt><dd><?= htmlspecialchars((string)($context['device']??'')) ?></dd></div><div><dt>Konum</dt><dd><?= htmlspecialchars(trim((string)($context['district']??'').' '.(string)($context['province']??'')) ?: 'Tüm Türkiye') ?></dd></div></dl></section>
    <form class="panel ad-context-test-form" method="get"><input type="hidden" name="placement" value="<?= htmlspecialchars((string)($context['placement']??'')) ?>"><label>Cihaz<select name="device"><option value="desktop" <?= ($context['device']??'')==='desktop'?'selected':'' ?>>Masaüstü</option><option value="mobile" <?= ($context['device']??'')==='mobile'?'selected':'' ?>>Mobil</option></select></label><label>İl<select name="province" data-location-province><?= TurkeyLocation::provinceOptions((string)($context['province']??''),'Tüm Türkiye') ?></select>" placeholder="Antalya"></label><label>İlçe<select name="district" data-location-district data-empty-label="Tüm ilçeler"><?= TurkeyLocation::districtOptions((string)($context['province']??''),(string)($context['district']??''),'Tüm ilçeler') ?></select>" placeholder="Alanya"></label><button class="btn btn-secondary" type="submit">Bu Bağlamda Test Et</button></form>
    <section class="panel ad-preview-stage"><span class="eyebrow">CANLI KART ÖNİZLEMESİ</span><?= AdRenderer::preview($ad) ?></section>
</main>
<?php require __DIR__.'/../../layouts/admin-footer.php'; ?>
