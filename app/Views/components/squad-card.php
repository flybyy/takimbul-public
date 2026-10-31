<?php
/** @var array<string,mixed> $member */
/** @var array<string,mixed> $team */
$displayName=(string)($member['display_name']??'Oyuncu');
$position=(string)($member['team_position']?:($member['primary_position']?:'Mevki yok'));
$score=max(0,min(100,(int)($member['form_score']??0)));
$formLabel=(string)($member['form_label']??'Veri Bekleniyor');
$accent=(string)($team['primary_color']??'#1f8f4e');if(!preg_match('/^#[0-9A-Fa-f]{6}$/',$accent))$accent='#1f8f4e';
$statusLabel=match((string)($member['squad_status']??'reserve')){'starter'=>'İlk Kadro','substitute'=>'Yedek',default=>'Rezerv'};
$roleLabel=match((string)($member['role']??'player')){'captain'=>'Kaptan','vice_captain'=>'Yrd. Kaptan',default=>''};
$avatar=trim((string)($member['avatar_path']??''));
?>
<a class="tb-squad-card" href="/kariyer/<?= (int)($member['id']??0) ?>" style="--team-accent:<?= htmlspecialchars($accent,ENT_QUOTES,'UTF-8') ?>">
    <span class="tb-squad-card-accent" aria-hidden="true"></span>
    <div class="tb-squad-card-top">
        <div class="tb-squad-rating"><?php if($score>0): ?><strong><?= $score ?></strong><small>TakımBul Form</small><?php else: ?><strong class="is-new">Yeni</strong><small>Yeni Oyuncu</small><?php endif; ?></div>
        <div class="tb-squad-meta"><?php if(!empty($member['jersey_number'])): ?><b>#<?= (int)$member['jersey_number'] ?></b><?php endif; ?><span><?= htmlspecialchars($position) ?></span></div>
    </div>
    <div class="tb-squad-avatar"><?php if($avatar!==''): ?><img src="<?= htmlspecialchars($avatar,ENT_QUOTES,'UTF-8') ?>" alt="<?= htmlspecialchars($displayName,ENT_QUOTES,'UTF-8') ?> profil fotoğrafı" loading="lazy"><?php else: ?><span><?= htmlspecialchars(strtoupper(substr($displayName,0,1))) ?></span><?php endif; ?></div>
    <div class="tb-squad-name"><strong><?= htmlspecialchars($displayName) ?></strong><small><?= htmlspecialchars($formLabel) ?></small></div>
    <div class="tb-squad-badges"><span><?= htmlspecialchars($statusLabel) ?></span><?php if($roleLabel!==''): ?><span><?= htmlspecialchars($roleLabel) ?></span><?php endif; ?></div>
    <div class="tb-squad-numbers"><span><b><?= (int)($member['appearances']??0) ?></b> Maç</span><span><b><?= (int)($member['goals']??0) ?></b> Gol</span><span><b><?= (int)($member['assists']??0) ?></b> Asist</span></div>
</a>
