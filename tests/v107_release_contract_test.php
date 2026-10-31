<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$notes=$root.'/docs/V10.7-RELEASE-NOTES.md';
$check=$root.'/docs/V10.7-LAUNCH-CHECKLIST.md';
expect_true(is_file($notes),'V10.7 release notes bulunmalı.');
expect_true(is_file($check),'V10.7 launch checklist bulunmalı.');
$notesText=(string)@file_get_contents($notes);$checkText=(string)@file_get_contents($check);$readme=(string)file_get_contents($root.'/README.md');
foreach(['Mavi Tik','Yazar','Yetkili','Sponsorlu','Audit'] as $term) expect_true(str_contains($notesText,$term),'Release notes '.$term.' kapsamını anlatmalı.');
foreach(['production-repair.php --apply','launch-check.php','production-smoke.php','2026-09-05-v107-verification-roles-sponsored-news.sql'] as $term) expect_true(str_contains($checkText,$term),'Launch checklist '.$term.' adımını içermeli.');
expect_true(stripos($checkText,'son aktif Süper Yönetici')!==false,'Launch checklist super-admin güvenlik kabulünü içermeli.');
expect_true(str_contains($readme,'V10.7'),'README güncel V10.7 sürümünü göstermeli.');
echo "PASS v107_release_contract_test\n";
