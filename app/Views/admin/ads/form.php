<?php use App\Core\TurkeyLocation;
use App\Core\Csrf;
require __DIR__.'/../../layouts/admin-header.php';
$id=(int)($ad['id']??0);$isEdit=$id>0;
$val=static fn(string $k): string=>htmlspecialchars((string)($ad[$k]??''),ENT_QUOTES,'UTF-8');
$dt=static function(string $v): string { $t=strtotime($v);return $t?date('Y-m-d\TH:i',$t):''; };
$placementLabels=['home_top'=>'Ana Sayfa Üst Sponsor Alanı','welcome_takeover'=>'İlk Ziyaret Sponsor Paneli','home_feed'=>'Ana Sayfa Feed','home_sidebar'=>'Ana Sayfa Yan Alan','players_feed'=>'Oyuncu Bul','opponents_feed'=>'Rakip Bul','match_inline'=>'Maç Sayfası','team_inline'=>'Takım Sayfası','career_inline'=>'Kariyer Sayfası','mobile_feed'=>'Mobil Feed','amateur_list_top'=>'Amatör Futbolcular Üst','amateur_list_inline'=>'Amatör Futbolcular Akış İçi','amateur_detail_inline'=>'Amatör Futbolcu Detay'];
?>
<main class="container section-space ad-form-page">
    <div class="section-head"><div><span class="eyebrow">REKLAM MERKEZİ</span><h1><?= $isEdit?'Reklamı Düzenle':'Yeni Reklam' ?></h1><p>Manuel sponsorlu kart veya Google AdSense gibi harici reklam kodu ekleyebilirsin.</p></div><a class="btn btn-ghost" href="/admin/reklamlar">← Reklamlar</a></div>
    <?php if($errors): ?><div class="flash flash-error"><div><?= htmlspecialchars((string)reset($errors)) ?></div></div><?php endif; ?>
    <form class="panel form-card ad-editor" method="post" action="<?= $isEdit?'/admin/reklamlar/'.$id.'/duzenle':'/admin/reklamlar/yeni' ?>">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
        <div class="form-grid two-col">
            <label>Reklam Türü<select name="type" id="adType"><option value="manual" <?= ($ad['type']??'manual')==='manual'?'selected':'' ?>>TakımBul Manuel Reklam</option><option value="external_code" <?= ($ad['type']??'')==='external_code'?'selected':'' ?>>Harici Reklam Kodu (AdSense vb.)</option></select></label>
            <label>Durum<select name="status"><option value="draft" <?= ($ad['status']??'')==='draft'?'selected':'' ?>>Taslak</option><option value="active" <?= ($ad['status']??'')==='active'?'selected':'' ?>>Aktif</option><option value="paused" <?= ($ad['status']??'')==='paused'?'selected':'' ?>>Duraklatıldı</option><option value="ended" <?= ($ad['status']??'')==='ended'?'selected':'' ?>>Bitti</option></select></label>
            <label>Reklamveren<input name="advertiser_name" maxlength="180" required value="<?= $val('advertiser_name') ?>" placeholder="Faralli Coffee"></label>
            <label>Başlık<input name="title" maxlength="180" required value="<?= $val('title') ?>" placeholder="Maç sonrası kahveni kap"></label>
        </div>
        <label>Açıklama<textarea name="body" maxlength="400" rows="3" placeholder="Kısa reklam metni"><?= $val('body') ?></textarea></label>
        <div class="manual-ad-fields">
            <div class="form-grid two-col"><label>Görsel URL / site içi yol<input name="image_url" value="<?= $val('image_url') ?>" placeholder="/uploads/ads/reklam.webp"></label><label>Hedef bağlantı<input type="url" name="target_url" value="<?= $val('target_url') ?>" placeholder="https://marka.com/kampanya"></label></div>
        </div>
        <div class="external-ad-fields">
            <label>Harici reklam kodu<textarea name="external_code" rows="9" spellcheck="false" placeholder="AdSense veya güvenilir reklam sağlayıcısının kodunu buraya yapıştır."><?= $val('external_code') ?></textarea></label>
            <p class="form-help warning-help">Bu alan ham HTML/JavaScript render eder ve yalnızca yönetici erişimine açıktır. Yalnızca güvendiğin reklam sağlayıcısının kodunu kullan.</p>
        </div>
        <div class="form-grid two-col">
            <label>Reklam Alanı<select name="placement"><?php foreach($placementLabels as $k=>$label): ?><option value="<?= $k ?>" <?= ($ad['placement']??'')===$k?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></label>
            <label>Cihaz<select name="device_target"><option value="all" <?= ($ad['device_target']??'all')==='all'?'selected':'' ?>>Tüm cihazlar</option><option value="desktop" <?= ($ad['device_target']??'')==='desktop'?'selected':'' ?>>Sadece masaüstü</option><option value="mobile" <?= ($ad['device_target']??'')==='mobile'?'selected':'' ?>>Sadece mobil</option></select></label>
            <label>İl (boş = tüm Türkiye)<select name="province" data-location-province><?= TurkeyLocation::provinceOptions((string)($ad['province']??''),'Tüm Türkiye') ?></select>" placeholder="Antalya"></label>
            <label>İlçe<select name="district" data-location-district data-empty-label="Tüm ilçeler"><?= TurkeyLocation::districtOptions((string)($ad['province']??''),(string)($ad['district']??''),'Tüm ilçeler') ?></select>" placeholder="Alanya"></label>
            <label>Başlangıç<input type="datetime-local" name="starts_at" required value="<?= htmlspecialchars($dt((string)($ad['starts_at']??''))) ?>"></label>
            <label>Bitiş<input type="datetime-local" name="ends_at" required value="<?= htmlspecialchars($dt((string)($ad['ends_at']??''))) ?>"></label>
        </div>
        <div class="form-actions"><button class="btn btn-primary" type="submit"><?= $isEdit?'Değişiklikleri Kaydet':'Reklamı Oluştur' ?></button><?php if($isEdit): ?><button class="btn btn-danger" type="submit" form="deleteAd">Reklamı Sil</button><?php endif; ?></div>
    </form>
    <?php if($isEdit): ?><form id="deleteAd" method="post" action="/admin/reklamlar/<?= $id ?>/sil" onsubmit="return confirm('Bu reklamı kalıcı olarak silmek istediğine emin misin?')"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>"></form><?php endif; ?>
</main>
<script>document.addEventListener('DOMContentLoaded',()=>{const t=document.getElementById('adType');const root=document.querySelector('.ad-editor');const sync=()=>root?.classList.toggle('is-external',t?.value==='external_code');t?.addEventListener('change',sync);sync();});</script>
<?php require __DIR__.'/../../layouts/admin-footer.php'; ?>
