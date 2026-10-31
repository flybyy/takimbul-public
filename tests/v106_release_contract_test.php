<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$readme=(string)file_get_contents($root.'/README.md');
$notes=(string)file_get_contents($root.'/docs/V10.6-RELEASE-NOTES.md');
$checklist=(string)file_get_contents($root.'/docs/V10.6-LAUNCH-CHECKLIST.md');
$launch=(string)file_get_contents($root.'/scripts/launch-check.php');
expect_true(str_contains($readme,'V10.6'),'README must state that V10.6 club/squad base is preserved by the current release.');
foreach(['docs/V10.6-RELEASE-NOTES.md','docs/V10.6-LAUNCH-CHECKLIST.md','tests/v106_club_browser_smoke.py','tests/v106_club_browser_smoke.sh','database/migrations/2026-09-05-v106-club-squad.sql'] as $file){
    expect_true(is_file($root.'/'.$file),'V10.6 release asset missing: '.$file);
}
expect_true(str_contains($notes,'Çoklu takım'),'V10.6 release notes must document multi-team membership.');
expect_true(str_contains($checklist,'production-smoke.php'),'V10.6 checklist must require production smoke.');
expect_true(str_contains($launch,"'V10.6 club squad migration'"),'V10.6 migration must be a critical launch gate.');
echo "PASS v106_release_contract_test\n";
