<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
$model=file_get_contents($root.'/app/Models/AmateurPlayerAd.php')?:'';

expect_true(str_contains($model,'profile_intent="both"'),'Expiry transition must recognize combined showcase + team-search profiles.');
expect_true(str_contains($model,'profile_intent="showcase"'),'Expiry transition must support showcase-only profiles.');
expect_true(str_contains($model,'profile_intent="showcase",expires_at=NULL'),'Finding a team must keep a non-expiring showcase profile.');
expect_true(str_contains($model,'(a.profile_intent="showcase" OR a.expires_at>=NOW())'),'Public list must not require expiry for showcase-only profiles.');
expect_true(str_contains($model,"\$intent==='showcase'?null:self::expiresAt(\$published)"),'Admin approval must not assign a 30-day expiry to showcase-only profiles.');

echo "PASS v102_player_showcase_persistence_test\n";
