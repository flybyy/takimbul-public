<?php use App\Core\TurkeyLocation;
use App\Core\Csrf;
$errors = $errors ?? [];
$old = $old ?? [];
require __DIR__ . '/../layouts/header.php';
?>
<main class="container section-space narrow-page">
    <div class="page-heading"><div><span class="eyebrow">TAKIM KUR</span><h1>Ekibini sahaya çıkar.</h1><p>Kaptan olarak takımını oluştur; sonraki adımda oyuncu veya rakip ilanı aç.</p></div></div>
    <section class="panel form-panel">
        <form method="post" action="/takim-olustur" class="form-grid">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <label>Takım adı<input name="name" required maxlength="120" value="<?= htmlspecialchars((string)($old['name'] ?? '')) ?>" placeholder="Alanya Kartalları"><?php if(isset($errors['name'])): ?><small class="field-error"><?= htmlspecialchars($errors['name']) ?></small><?php endif; ?></label>
            <div class="form-row-2">
                <label>İl<select name="province" data-location-province required><?= TurkeyLocation::provinceOptions((string)($old['province']??''),'İl seç') ?></select>" placeholder="Antalya"></label>
                <label>İlçe<select name="district" data-location-district data-empty-label="İlçe seç" required><?= TurkeyLocation::districtOptions((string)($old['province']??''),(string)($old['district']??''),'İlçe seç') ?></select>" placeholder="Alanya"></label>
            </div>
            <label>Kuruluş yılı<input type="number" name="founded_year" min="1900" max="<?= date('Y') ?>" value="<?= htmlspecialchars((string)($old['founded_year'] ?? date('Y'))) ?>"></label>
            <label>Takım açıklaması<textarea name="description" maxlength="700" rows="5" placeholder="Takımınızın oyun seviyesi, maç günleri, hedefleri..."><?= htmlspecialchars((string)($old['description'] ?? '')) ?></textarea></label>
            <button class="btn btn-primary" type="submit">Takımı Oluştur</button>
        </form>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
