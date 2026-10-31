<?php

declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];$ok=function(bool $c,string $m)use(&$fail){if(!$c)$fail[]=$m;};
$cssPath=$root.'/public/assets/css/app-v108.css';
$ok(is_file($cssPath),'app-v108.css must exist');
$css=is_file($cssPath)?(file_get_contents($cssPath)?:''):'';
foreach(['TAKIMBUL V10.8 FOOTBALL IDENTITIES','identity-picker-grid','professional-card-grid','professional-profile-hero','professional-form','professional-inline-row','professional-nav-cluster','identity-account-grid','admin-section-head'] as $needle){$ok(str_contains($css,$needle),'missing V10.8 CSS contract: '.$needle);}
foreach(['app/Views/layouts/header.php','app/Views/layouts/admin-header.php','app/Views/admin/auth/login.php'] as $file){$src=file_get_contents($root.'/'.$file)?:'';$ok(str_contains($src,'app-v108.css'),'must use app-v108.css: '.$file);}
$launch=file_get_contents($root.'/scripts/launch-check.php')?:'';$ok(str_contains($launch,'V10.8 football identities asset senkronu'),'launch-check must verify V10.8 asset sync');
foreach(['docs/V10.8-IDENTITIES-RELEASE-NOTES.md','docs/V10.8-IDENTITIES-LAUNCH-CHECKLIST.md'] as $file){$ok(is_file($root.'/'.$file),'missing release file: '.$file);}
if($fail){fwrite(STDERR,"FAIL\n - ".implode("\n - ",$fail)."\n");exit(1);}echo "PASS v108_identities_visual_release_contract_test\n";
