<?php use App\Core\TurkeyLocation;
use App\Core\Csrf;
use App\Models\PlayerProfile;
$profile=$profile??[];$identity=$identity??[];$errors=$errors??[];
$avatar=(string)($profile['avatar_path']??'');
$initial=strtoupper(substr((string)($identity['display_name']??'T'),0,1));
require __DIR__.'/../layouts/header.php';
?>
<main class="container section-space profile-editor-page">
    <div class="page-heading"><div><span class="eyebrow">PROFİL MERKEZİ</span><h1>Futbol kimliğini eksiksiz tamamla.</h1><p>Takımların ve scoutların seni doğru tanıyabilmesi için temel bilgilerini ve saha profilini güncel tut.</p></div><a class="btn btn-ghost" href="/kariyer/<?= (int)\App\Core\Auth::id() ?>">Kariyerimi Gör</a></div>
    <?php if($errors): ?><div class="alert alert-error"><strong>Kaydedilemedi.</strong> <?= htmlspecialchars((string)reset($errors)) ?></div><?php endif; ?>
    <form method="post" action="/profil/duzenle" enctype="multipart/form-data" class="profile-editor-grid">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(),ENT_QUOTES,'UTF-8') ?>">
        <aside class="panel profile-photo-panel">
            <span class="eyebrow">PROFİL FOTOĞRAFI</span>
            <div class="profile-avatar-preview"><?php if($avatar!==''): ?><img src="<?= htmlspecialchars($avatar,ENT_QUOTES,'UTF-8') ?>" alt="Profil fotoğrafı"><?php else: ?><span><?= htmlspecialchars($initial) ?></span><?php endif; ?></div>
            <label class="file-picker">Fotoğraf seç<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"></label>
            <small>JPEG, PNG veya WebP · maksimum 3 MB. Büyük görseller otomatik küçültülür.</small>
            <div class="profile-completion-note"><b>@<?= htmlspecialchars((string)($identity['username']??'kullaniciadi')) ?></b><span><?= htmlspecialchars((string)($identity['district']??'')) ?><?= !empty($identity['province'])?', '.htmlspecialchars((string)$identity['province']):'' ?></span></div>
        </aside>
        <section class="panel form-panel profile-main-form">
            <div class="form-section-title"><span>01</span><div><strong>Hesap kimliği</strong><small>Sitede görünen temel bilgiler</small></div></div>
            <div class="form-row-2">
                <label>Görünen ad<input type="text" name="display_name" maxlength="100" required value="<?= htmlspecialchars((string)($identity['display_name']??''),ENT_QUOTES,'UTF-8') ?>"></label>
                <label>Kullanıcı adı<div class="input-prefix"><span>@</span><input type="text" name="username" maxlength="30" required value="<?= htmlspecialchars((string)($identity['username']??''),ENT_QUOTES,'UTF-8') ?>" placeholder="murat.dincer"></div></label>
            </div>
            <div class="form-row-2"><label>İl<select name="province" data-location-province required><?= TurkeyLocation::provinceOptions((string)($identity['province']??''),'İl seç') ?></select></label><label>İlçe<select name="district" data-location-district data-empty-label="İlçe seç" required><?= TurkeyLocation::districtOptions((string)($identity['province']??''),(string)($identity['district']??''),'İlçe seç') ?></select></label></div>

            <div class="form-section-title"><span>02</span><div><strong>Saha profili</strong><small>Mevki ve fiziksel bilgiler</small></div></div>
            <div class="form-row-2">
                <label>Ana mevki<select name="primary_position"><option value="">Seç</option><?php foreach(PlayerProfile::POSITIONS as $position): ?><option <?= (($profile['primary_position']??'')===$position)?'selected':'' ?>><?= htmlspecialchars($position) ?></option><?php endforeach; ?></select></label>
                <label>Alternatif mevki<select name="secondary_position"><option value="">Seç</option><?php foreach(PlayerProfile::POSITIONS as $position): ?><option <?= (($profile['secondary_position']??'')===$position)?'selected':'' ?>><?= htmlspecialchars($position) ?></option><?php endforeach; ?></select></label>
            </div>
            <div class="form-row-3"><label>Dominant ayak<select name="dominant_foot"><option value="">Seç</option><?php foreach(PlayerProfile::FEET as $foot): ?><option <?= (($profile['dominant_foot']??'')===$foot)?'selected':'' ?>><?= htmlspecialchars($foot) ?></option><?php endforeach; ?></select></label><label>Doğum yılı<input type="number" name="birth_year" min="1940" max="<?= date('Y')-8 ?>" value="<?= htmlspecialchars((string)($profile['birth_year']??'')) ?>"></label><label>Boy (cm)<input type="number" name="height_cm" min="120" max="230" value="<?= htmlspecialchars((string)($profile['height_cm']??'')) ?>"></label></div>
            <label>Futbol özeti<textarea name="bio" maxlength="500" rows="5" placeholder="Oyun stilin, güçlü yönlerin, hedeflerin..."><?= htmlspecialchars((string)($profile['bio']??'')) ?></textarea></label>
            <div class="form-row-2"><label>Profil görünürlüğü<select name="visibility"><option value="public" <?= (($profile['visibility']??'public')==='public')?'selected':'' ?>>Herkese Açık</option><option value="members" <?= (($profile['visibility']??'')==='members')?'selected':'' ?>>Sadece Üyeler</option><option value="private" <?= (($profile['visibility']??'')==='private')?'selected':'' ?>>Gizli</option></select></label><label class="check-row editor-check"><input type="checkbox" name="show_age" value="1" <?= !isset($profile['show_age'])||!empty($profile['show_age'])?'checked':'' ?>> Yaşımı kariyer profilinde göster</label></div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Profili Kaydet</button><a class="btn btn-ghost" href="/kariyer/<?= (int)\App\Core\Auth::id() ?>">Vazgeç</a></div>
        </section>
    </form>
</main>
<?php require __DIR__.'/../layouts/footer.php'; ?>
