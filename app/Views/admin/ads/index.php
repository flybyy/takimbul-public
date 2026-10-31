<?php use App\Core\TurkeyLocation;
use App\Core\Csrf;
use App\Models\Ad;
require __DIR__.'/../../layouts/admin-header.php';
$placementLabels=['home_top'=>'Ana Sayfa Üst Sponsor Alanı','welcome_takeover'=>'İlk Ziyaret Sponsor Paneli','home_feed'=>'Ana Sayfa Feed','home_sidebar'=>'Ana Sayfa Yan Alan','players_feed'=>'Oyuncu Bul','opponents_feed'=>'Rakip Bul','match_inline'=>'Maç Sayfası','team_inline'=>'Takım Sayfası','career_inline'=>'Kariyer Sayfası','mobile_feed'=>'Mobil Feed','amateur_list_top'=>'Amatör Futbolcular Üst','amateur_list_inline'=>'Amatör Futbolcular Akış İçi','amateur_detail_inline'=>'Amatör Futbolcu Detay'];
$statusLabels=['draft'=>'Taslak','active'=>'Aktif','paused'=>'Duraklatıldı','ended'=>'Bitti'];
?>
<main class="container section-space ad-admin-page">
    <div class="section-head"><div><span class="eyebrow">YÖNETİM</span><h1>Reklam Merkezi</h1><p>TakımBul içi sponsorlu kampanyaları ve harici reklam kodlarını tek yerden yönet.</p></div><a class="btn btn-primary" href="/admin/reklamlar/yeni">+ Yeni Reklam</a></div>
    <form class="ad-context-banner ad-delivery-tester" method="get" action="/admin/reklamlar"><div><span>TESLİMAT TESTİ</span><small>Ayrı yönetici hesabının oyuncu konumu yoktur. Reklamı istediğin cihaz ve bölgeyle burada simüle et.</small></div><label>Cihaz<select name="device"><option value="desktop" <?= ($deliveryContext['device']??'desktop')==='desktop'?'selected':'' ?>>Masaüstü</option><option value="mobile" <?= ($deliveryContext['device']??'desktop')==='mobile'?'selected':'' ?>>Mobil</option></select></label><label>İl<select name="province" data-location-province><?= TurkeyLocation::provinceOptions((string)($deliveryContext['province']??''),'Tüm Türkiye') ?></select></label><label>İlçe<select name="district" data-location-district data-empty-label="Tüm ilçeler"><?= TurkeyLocation::districtOptions((string)($deliveryContext['province']??''),(string)($deliveryContext['district']??''),'Tüm ilçeler') ?></select></label><button class="btn btn-soft btn-small" type="submit">Teslimatı Test Et</button></form>
    <div class="admin-ad-summary">
        <?php $totalImp=0;$totalClicks=0;$active=0;foreach($ads as $a){$totalImp+=(int)$a['impressions'];$totalClicks+=(int)$a['clicks'];if($a['status']==='active')$active++;} ?>
        <div class="panel stat-card"><strong><?= count($ads) ?></strong><span>Toplam reklam</span></div>
        <div class="panel stat-card"><strong><?= $active ?></strong><span>Aktif reklam</span></div>
        <div class="panel stat-card"><strong><?= number_format($totalImp,0,',','.') ?></strong><span>Gösterim</span></div>
        <div class="panel stat-card"><strong><?= number_format($totalClicks,0,',','.') ?></strong><span>Tıklama</span></div>
        <div class="panel stat-card"><strong>%<?= number_format(Ad::ctr($totalClicks,$totalImp),2,',','.') ?></strong><span>Genel CTR</span></div>
    </div>
    <div class="panel table-panel ad-admin-table-wrap">
        <table class="data-table ad-admin-table"><thead><tr><th>Reklam</th><th>Alan</th><th>Hedef</th><th>Dönem</th><th>Gösterim</th><th>Tıklama</th><th>CTR</th><th>Teslimat</th><th>Durum</th><th></th></tr></thead><tbody>
        <?php if(!$ads): ?><tr><td colspan="10"><div class="empty-state">Henüz reklam yok. İlk kampanyanı oluştur.</div></td></tr><?php endif; ?>
        <?php foreach($ads as $ad): ?>
        <tr>
            <td><strong><?= htmlspecialchars((string)$ad['title']) ?></strong><small><?= htmlspecialchars((string)$ad['advertiser_name']) ?> · <?= $ad['type']==='external_code'?'Harici kod':'Manuel' ?></small></td>
            <td><?= htmlspecialchars($placementLabels[$ad['placement']]??(string)$ad['placement']) ?><small><?= htmlspecialchars((string)$ad['device_target']) ?></small></td>
            <td><?= htmlspecialchars(trim(((string)($ad['province']??'')).' '.((string)($ad['district']??''))) ?: 'Tüm Türkiye') ?></td>
            <td><small><?= htmlspecialchars(date('d.m.Y',strtotime((string)$ad['starts_at']))) ?> → <?= htmlspecialchars(date('d.m.Y',strtotime((string)$ad['ends_at']))) ?></small></td>
            <td><?= number_format((int)$ad['impressions'],0,',','.') ?></td><td><?= number_format((int)$ad['clicks'],0,',','.') ?></td><td>%<?= number_format(Ad::ctr((int)$ad['clicks'],(int)$ad['impressions']),2,',','.') ?></td>
            <?php $diag=Ad::diagnose($ad,['placement'=>(string)$ad['placement'],'device'=>(string)($deliveryContext['device']??'desktop'),'province'=>(string)($deliveryContext['province']??''),'district'=>(string)($deliveryContext['district']??'')]); ?>
            <td><span class="delivery-pill <?= !empty($diag['ok'])?'ok':'warn' ?>"><?= htmlspecialchars((string)$diag['label']) ?></span><small><?= htmlspecialchars((string)$diag['detail']) ?></small></td>
            <td><span class="status-badge status-<?= htmlspecialchars((string)$ad['status']) ?>"><?= htmlspecialchars($statusLabels[$ad['status']]??(string)$ad['status']) ?></span></td>
            <td><div class="table-actions"><a class="btn btn-ghost btn-small" href="/admin/reklamlar/<?= (int)$ad['id'] ?>/onizle">Önizle</a><a class="btn btn-ghost btn-small" href="/admin/reklamlar/<?= (int)$ad['id'] ?>/duzenle">Düzenle</a><form method="post" action="/admin/reklamlar/<?= (int)$ad['id'] ?>/durum"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>"><input type="hidden" name="status" value="<?= $ad['status']==='active'?'paused':'active' ?>"><button class="btn btn-ghost btn-small" type="submit"><?= $ad['status']==='active'?'Durdur':'Aktif Et' ?></button></form></div></td>
        </tr><?php endforeach; ?>
        </tbody></table>
    </div>
</main>
<?php require __DIR__.'/../../layouts/admin-footer.php'; ?>
