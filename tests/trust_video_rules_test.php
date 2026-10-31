<?php
declare(strict_types=1);require __DIR__.'/bootstrap.php';
use App\Models\TrustScore;use App\Models\PlayerVideo;
expect_true(class_exists(TrustScore::class),'TrustScore model must exist');
expect_true(class_exists(PlayerVideo::class),'PlayerVideo model must exist');
$good=TrustScore::fromCounts(20,12,1,0);
$weak=TrustScore::fromCounts(4,1,3,2);
expect_true($good['score']>$weak['score'],'reliable player should have higher trust score');
expect_true($good['score']<=100 && $weak['score']>=0,'trust score must stay 0..100');
expect_same(null,PlayerVideo::validateUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'),'YouTube URL should pass');
expect_same(null,PlayerVideo::validateUrl('https://www.instagram.com/reel/ABC123/'),'Instagram URL should pass');
expect_true(PlayerVideo::validateUrl('javascript:alert(1)')!==null,'unsafe scheme should fail');
expect_true(PlayerVideo::validateUrl('https://evil.example/video')!==null,'unsupported host should fail');
echo "PASS trust_video_rules_test\n";
