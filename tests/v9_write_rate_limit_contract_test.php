<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$availability=file_get_contents($root.'/app/Controllers/AvailabilityController.php')?:'';
$career=file_get_contents($root.'/app/Controllers/CareerController.php')?:'';
$check(str_contains($availability,'RateLimiter') && str_contains($availability,"availability_publish") && str_contains($availability,"availability_invite"),'availability publishing and invitations must be rate limited');
$check(str_contains($career,'RateLimiter') && str_contains($career,"video_showcase"),'video showcase writes must be rate limited');
if($fail){fwrite(STDERR,"FAIL v9_write_rate_limit_contract_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v9_write_rate_limit_contract_test\n";
