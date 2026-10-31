<?php require __DIR__.'/../layouts/admin-header.php';$recentNews=$recentNews??[]; ?>
<main class="container section-space v9-page admin-v9-page">
  <section class="v9-page-head">
    <div><span class="v9-eyebrow">EDİTORYAL</span><h1>Yazar Paneli</h1><p>Haberlerini oluştur, taslaklarını düzenle ve yayın durumunu takip et.</p></div>
    <a class="btn btn-primary" href="/admin/haberler/yeni">Yeni Haber</a>
  </section>
  <section class="admin-metric-grid">
    <a class="admin-metric-card" href="/admin/haberler"><span>Haberlerim</span><strong><?=count($recentNews)?></strong><small>Son kayıtların</small></a>
    <a class="admin-metric-card" href="/admin/haberler/yeni"><span>Yeni İçerik</span><strong>+</strong><small>Haber oluştur</small></a>
  </section>
  <section class="v9-surface admin-news-summary"><div class="v9-section-title"><div><span class="v9-eyebrow">İÇERİK</span><h2>Son Haberlerim</h2></div><a href="/admin/haberler">Tümünü gör</a></div><div class="v9-list">
  <?php foreach($recentNews as $newsItem):?><a class="v9-list-row" href="/admin/haberler/<?=(int)$newsItem['id']?>/duzenle"><span class="admin-ad-dot <?=$newsItem['status']==='published'?'active':'draft'?>"></span><div><strong><?=htmlspecialchars((string)$newsItem['title'])?></strong><small><?=htmlspecialchars((string)$newsItem['category'])?> · <?=$newsItem['status']==='published'?'Yayında':'Taslak'?> · <?=number_format((int)$newsItem['view_count'],0,',','.')?> görüntülenme</small></div><b>→</b></a><?php endforeach;?>
  <?php if(!$recentNews):?><div class="v9-empty"><strong>Henüz haberin yok.</strong><a href="/admin/haberler/yeni">İlk haberi oluştur</a></div><?php endif;?>
  </div></section>
</main><?php require __DIR__.'/../layouts/admin-footer.php'; ?>
