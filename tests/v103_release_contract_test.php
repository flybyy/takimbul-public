<?php
declare(strict_types=1);
function require_true(bool $ok,string $message): void { if(!$ok){fwrite(STDERR,"FAIL v103_release_contract_test\n- {$message}\n");exit(1);} }
$root=dirname(__DIR__);
$launch=(string)file_get_contents($root.'/scripts/launch-check.php');
foreach(['app/Views/errors/429.php','tests/v103_browser_smoke.py','tests/v103_browser_smoke.sh','docs/V10.3-RELEASE-NOTES.md','docs/V10.3-LAUNCH-CHECKLIST.md'] as $file){require_true(is_file($root.'/'.$file),'V10.3 release asset missing: '.$file);}
foreach(['V10.3 responsive katmanı','V10.3 auth submit guard','V10.3 429 hata ekranı','V10.3 browser smoke'] as $needle){require_true(str_contains($launch,$needle),'launch-check marker missing: '.$needle);}
$sh=(string)file_get_contents($root.'/tests/v103_browser_smoke.sh');
require_true(str_contains($sh,'v103_browser_smoke.py'),'Browser smoke shell wrapper missing Python invocation.');
echo "PASS v103_release_contract_test\n";
