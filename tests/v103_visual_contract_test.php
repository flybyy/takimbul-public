<?php
declare(strict_types=1);

function expect_true(bool $condition,string $message): void { if(!$condition){fwrite(STDERR,"FAIL v103_visual_contract_test\n- {$message}\n");exit(1);} }
$css=(string)file_get_contents(__DIR__.'/../public/assets/css/app.css');
$header=(string)file_get_contents(__DIR__.'/../app/Views/layouts/header.php');
$list=(string)file_get_contents(__DIR__.'/../app/Views/amateur-market/index.php');
$show=(string)file_get_contents(__DIR__.'/../app/Views/amateur-market/show.php');

expect_true(str_contains($css,'TAKIMBUL V10.3 FINAL LAUNCH'),'V10.3 CSS layer marker missing.');
expect_true(str_contains($css,':focus-visible'),'Keyboard focus-visible treatment missing.');
expect_true(str_contains($css,'overflow-wrap:anywhere'),'Long-content overflow safeguard missing.');
expect_true(str_contains($css,'@media(max-width:430px)'),'430px mobile breakpoint missing.');
expect_true(str_contains($css,'@media(max-width:360px)'),'360px compact-mobile breakpoint missing.');
expect_true(str_contains($css,'.v103-header-safe'),'V10.3 header responsive marker missing.');
expect_true(str_contains($header,'v103-header-safe'),'Header does not opt into V10.3 responsive shell.');
expect_true(str_contains($header,'aria-label="TakımBul genel arama"'),'Header search accessibility label missing.');
foreach(['OYUNCU VİTRİNİ','VİTRİN · TAKIM ARIYOR','TAKIM ARIYOR'] as $label){
    expect_true(str_contains($list,$label) || str_contains($show,$label),'Showcase intent label missing: '.$label);
}
expect_true(str_contains($list,'v103-player-card'),'Player card V10.3 wrapping marker missing.');
expect_true(str_contains($show,'v103-player-detail'),'Player detail V10.3 responsive marker missing.');

echo "PASS v103_visual_contract_test\n";
