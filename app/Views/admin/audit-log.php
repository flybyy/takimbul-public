<?php require __DIR__.'/../layouts/admin-header.php';$entries=$entries??[]; ?>
<main class="container section-space v9-page admin-v9-page">
  <section class="v9-page-head"><div><span class="v9-eyebrow">GÜVENLİK & İZ</span><h1>Audit Log</h1><p>Yönetim panelindeki kritik işlemlerin salt-okunur kayıtları.</p></div></section>
  <section class="v9-surface"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Zaman</th><th>Yetkili</th><th>İşlem</th><th>Hedef</th><th>Detay</th></tr></thead><tbody>
  <?php foreach($entries as $entry):?>
    <?php $meta=[];if(!empty($entry['metadata_json'])){$decoded=json_decode((string)$entry['metadata_json'],true);if(is_array($decoded))$meta=$decoded;} ?>
    <tr><td><?=htmlspecialchars((string)$entry['created_at'])?></td><td><?=htmlspecialchars((string)($entry['actor_name']?:$entry['actor_username']?:'Sistem'))?></td><td><code><?=htmlspecialchars((string)$entry['action'])?></code></td><td><?=htmlspecialchars((string)$entry['target_type'])?><?php if(!empty($entry['target_id'])):?> #<?=(int)$entry['target_id']?><?php endif;?></td><td><?php if($meta):?><details><summary>Göster</summary><pre><?=htmlspecialchars((string)json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT))?></pre></details><?php else:?><span>—</span><?php endif;?></td></tr>
  <?php endforeach;?>
  <?php if(!$entries):?><tr><td colspan="5">Henüz audit kaydı yok.</td></tr><?php endif;?>
  </tbody></table></div></section>
</main><?php require __DIR__.'/../layouts/admin-footer.php'; ?>
