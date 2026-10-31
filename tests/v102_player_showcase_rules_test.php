<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Models\AmateurPlayerAd;

expect_true(defined(AmateurPlayerAd::class.'::PROFILE_INTENTS'),'Profile intent options are missing.');
expect_same(['team_search','showcase','both'],AmateurPlayerAd::PROFILE_INTENTS,'Profile intent options changed unexpectedly.');

$base=[
 'province'=>'Antalya','district'=>'Alanya','primary_position'=>'Orta Saha','secondary_position'=>'Kanat',
 'birth_year'=>(string)((int)date('Y')-20),'height_cm'=>'178','dominant_foot'=>'Sağ','license_status'=>'free',
 'last_club'=>'Alanya Gençlik','football_story'=>'Kendimi ve futbol geçmişimi tanıtıyorum.',
 'preferred_cities'=>'','availability_text'=>'','contact_preference'=>'takimbul','whatsapp_public'=>'0','video_url'=>''
];

$showcase=$base+['profile_intent'=>'showcase','desired_levels'=>[]];
expect_same([],AmateurPlayerAd::validateInput($showcase),'Showcase-only profile must not require transfer targets.');

$search=$base+['profile_intent'=>'team_search','desired_levels'=>[]];
expect_true(isset(AmateurPlayerAd::validateInput($search)['desired_levels']),'Team-search profile must require at least one target level.');

$both=$base+['profile_intent'=>'both','desired_levels'=>['1. Amatör']];
expect_same([],AmateurPlayerAd::validateInput($both),'Combined showcase and team-search profile should validate.');

$invalid=$both;$invalid['profile_intent']='anything';
expect_true(isset(AmateurPlayerAd::validateInput($invalid)['profile_intent']),'Unknown profile intent must fail validation.');

expect_true(AmateurPlayerAd::isTransferOpen(['profile_intent'=>'team_search','status'=>'active']),'Active team-search profile must accept transfer interest.');
expect_true(AmateurPlayerAd::isTransferOpen(['profile_intent'=>'both','status'=>'active']),'Active combined profile must accept transfer interest.');
expect_true(!AmateurPlayerAd::isTransferOpen(['profile_intent'=>'showcase','status'=>'active']),'Showcase-only profile must not accept team invites.');
expect_true(!AmateurPlayerAd::isTransferOpen(['profile_intent'=>'both','status'=>'paused']),'Paused profile must not accept team invites.');

echo "PASS v102_player_showcase_rules_test\n";
