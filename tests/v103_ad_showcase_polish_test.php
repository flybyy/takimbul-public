<?php
declare(strict_types=1);
function assert_ok(bool $ok,string $message): void { if(!$ok){fwrite(STDERR,"FAIL v103_ad_showcase_polish_test\n- {$message}\n");exit(1);} }
$renderer=(string)file_get_contents(__DIR__.'/../app/Core/AdRenderer.php');
$css=(string)file_get_contents(__DIR__.'/../public/assets/css/app.css');
$list=(string)file_get_contents(__DIR__.'/../app/Views/amateur-market/index.php');
$show=(string)file_get_contents(__DIR__.'/../app/Views/amateur-market/show.php');
assert_ok(str_contains($renderer,'aria-label="Sponsorlu içerik"'),'Ad wrapper sponsored aria label missing.');
assert_ok(str_contains($renderer,'loading="lazy" decoding="async"'),'Manual ad image loading contract missing.');
assert_ok(str_contains($renderer,'width="640" height="360"'),'Manual ad image intrinsic ratio contract missing.');
assert_ok(str_contains($renderer,'rel="sponsored noopener"'),'Sponsored CTA rel hardening missing.');
assert_ok(str_contains($css,'.ad-external-frame{') && str_contains($css,'overflow:hidden'),'External ad containment missing.');
assert_ok(str_contains($css,'.ad-card-media img') && str_contains($css,'object-fit:cover'),'Ad image containment missing.');
assert_ok(str_contains($css,'.ad-slot:empty{display:none}'),'Empty ad slot collapse missing.');
assert_ok(str_contains($css,'.v103-player-card') && str_contains($css,'overflow-wrap:anywhere'),'Player card wrap hardening missing.');
assert_ok(str_contains($list,"AdRenderer::slot('amateur_list_inline'"),'Inline amateur ad slot missing.');
assert_ok(str_contains($show,"AdRenderer::slot('amateur_detail_inline'"),'Detail amateur ad slot missing.');
echo "PASS v103_ad_showcase_polish_test\n";
