<?php

declare(strict_types=1);
require dirname(__DIR__).'/app/Models/FootballIdentity.php';
use App\Models\FootballIdentity;

$fail=[];
$types=FootballIdentity::normalizeTypes(['referee','coach','referee','bogus']);
if($types!==['referee','coach'])$fail[]='normalize whitelist/dedupe';
if(FootballIdentity::normalizeTypes([])!==['recreational_player'])$fail[]='safe default recreational';
if(FootballIdentity::label('referee')!=='Hakem')$fail[]='referee label';
if(FootballIdentity::onboardingPathForType('amateur_player')!=='/amator-ilanim')$fail[]='amateur path';
if(FootballIdentity::onboardingPathForType('referee')!=='/hakem-profilim')$fail[]='referee path';
if(FootballIdentity::onboardingPathForType('coach')!=='/teknik-direktor-profilim')$fail[]='coach path';
if(FootballIdentity::onboardingPathForType('club_official')!=='/takim-olustur')$fail[]='official path';
if($fail!==[]){fwrite(STDERR,'FAIL: '.implode(' | ',$fail)."\n");exit(1);}echo "v108 identity model PASS\n";
