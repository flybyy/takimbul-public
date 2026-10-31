<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\AmateurPlayerAd;
use App\Models\AmateurCareerEntry;

expect_true(class_exists(AmateurPlayerAd::class),'AmateurPlayerAd model missing.');
expect_true(class_exists(AmateurCareerEntry::class),'AmateurCareerEntry model missing.');
expect_true(in_array('active',AmateurPlayerAd::STATUSES,true),'Active status missing.');
expect_true(in_array('found_team',AmateurPlayerAd::STATUSES,true),'found_team status missing.');
expect_true(in_array('free',AmateurPlayerAd::LICENSE_STATUSES,true),'Free license status missing.');

$valid=[
 'province'=>'Antalya','district'=>'Alanya','primary_position'=>'Orta Saha','secondary_position'=>'Kanat',
 'birth_year'=>(string)((int)date('Y')-20),'height_cm'=>'178','dominant_foot'=>'Sağ','license_status'=>'free',
 'desired_levels'=>['Süper Amatör','1. Amatör'],'last_club'=>'Alanya Gençlik','football_story'=>'Orta saha oynuyorum.',
 'preferred_cities'=>'Antalya, Isparta','availability_text'=>'Hafta içi akşam','contact_preference'=>'takimbul',
 'whatsapp_public'=>'0','video_url'=>'https://www.youtube.com/watch?v=test'
];
expect_same([],AmateurPlayerAd::validateInput($valid),'Valid amateur ad should pass validation.');
$bad=$valid;$bad['video_url']='javascript:alert(1)';
expect_true(isset(AmateurPlayerAd::validateInput($bad)['video_url']),'Unsafe video URL must fail.');
expect_true(str_starts_with(AmateurPlayerAd::expiresAt('2026-09-02 12:00:00'),'2026-10-02'),'Expiry must be 30 days after publication.');
expect_true(AmateurPlayerAd::canExposeWhatsapp(['whatsapp_public'=>1,'contact_preference'=>'both'],20),'Adult explicit WhatsApp preference should expose.');
expect_true(!AmateurPlayerAd::canExposeWhatsapp(['whatsapp_public'=>1,'contact_preference'=>'both'],17),'Minor WhatsApp must stay private.');

$rows=[[
 'season'=>'2025/26','club_name'=>'Alanya Gençlik','level'=>'1. Amatör','position'=>'Orta Saha','jersey_number'=>'8',
 'matches'=>'18','starts'=>'14','goals'=>'7','assists'=>'9','yellow_cards'=>'2','red_cards'=>'0','note'=>'Düzenli oynadım.'
]];
expect_same([],AmateurCareerEntry::validateRows($rows),'Valid career row should pass.');
$badRows=$rows;$badRows[0]['starts']='19';
expect_true(AmateurCareerEntry::validateRows($badRows)!==[],'Starts greater than matches must fail.');
$tooMany=array_fill(0,13,$rows[0]);
expect_true(AmateurCareerEntry::validateRows($tooMany)!==[],'More than 12 career rows must fail.');
$summary=AmateurCareerEntry::summary($rows);
expect_same(18,$summary['matches'],'Career summary matches incorrect.');
expect_same(7,$summary['goals'],'Career summary goals incorrect.');
expect_same(9,$summary['assists'],'Career summary assists incorrect.');
echo "PASS v101_amateur_market_rules_test\n";
