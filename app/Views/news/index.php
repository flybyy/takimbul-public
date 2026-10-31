<?php
use App\Models\News;
$excerpt=static function(string $text,int $limit=150): string { $plain=trim(strip_tags($text));$cut=function_exists('mb_substr')?mb_substr($plain,0,$limit,'UTF-8'):substr($plain,0,$limit);return $cut.((function_exists('mb_strlen')?mb_strlen($plain,'UTF-8'):strlen($plain))>$limit?'…':''); };
require __DIR__.'/../layouts/header.php';
?>
<main class="news-page section-space">
<section class="container news-hero"><div><span class="eyebrow">TAKIMBUL HABER</span><h1>Sahadan ve amatör futboldan haberler.</h1><p>TakımBul gelişmeleri, amatör futbol, transfer, halı saha ve topluluk duyuruları.</p></div></section>
<section class="container news-grid" aria-label="Haberler">
<?php foreach(($newsItems??[]) as $item): $img=News::safeImageUrl((string)($item['cover_image_url']??'')); ?>
<a class="news-card" href="/haber/<?= rawurlencode((string)$item['slug']) ?>">
<?php if($img):?><div class="news-card-media"><img src="<?= htmlspecialchars($img,ENT_QUOTES,'UTF-8') ?>" alt="" loading="lazy" width="720" height="405"></div><?php else:?><div class="news-card-media news-card-placeholder"><span>takim<b>bul</b></span></div><?php endif;?>
<div class="news-card-body"><?php if(!empty($item['sponsor_id'])):?><span class="tb-sponsored-badge">Sponsorlu</span><?php endif;?><div class="news-meta"><span><?= htmlspecialchars((string)$item['category']) ?></span><time datetime="<?= htmlspecialchars((string)$item['published_at']) ?>"><?= date('d.m.Y',strtotime((string)$item['published_at'])) ?></time></div><div class="news-card-author"><?php if(!empty($item['author_name'])):?><?=htmlspecialchars((string)$item['author_name'])?><?php if(!empty($item['author_title'])):?><small><?=htmlspecialchars((string)$item['author_title'])?></small><?php endif;?><?php else:?>TakımBul<?php endif;?></div><h2><?= htmlspecialchars((string)$item['title']) ?></h2><p><?= htmlspecialchars((string)($item['summary']?:$excerpt((string)$item['body']))) ?></p><strong>Haberi oku →</strong></div>
</a>
<?php endforeach;?>
<?php if(empty($newsItems)):?><div class="v9-empty span-all news-empty"><strong>Henüz yayınlanmış haber yok.</strong><p>TakımBul ve amatör futbol gündemi burada yayınlanacak.</p></div><?php endif;?>
</section>
</main>
<?php require __DIR__.'/../layouts/footer.php'; ?>
