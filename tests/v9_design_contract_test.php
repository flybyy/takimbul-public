<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fail=[];$check=static function(bool $v,string $m)use(&$fail){if(!$v)$fail[]=$m;};
$css=file_get_contents($root.'/public/assets/css/app.css')?:'';$js=file_get_contents($root.'/public/assets/js/app.js')?:'';
foreach(['--v9-ink:#171a18','--v9-paper:#f6f7f4','--v9-green:#1e8e4a','.v9-command{','.v9-mobile-sheet{','.matchday-shell{','.availability-grid{','.career-v9-scoreboard{'] as $needle)$check(str_contains(str_replace(' ', '', strtolower($css)),str_replace(' ', '', strtolower($needle))),'CSS missing '.$needle);
foreach(['data-v9-sheet-open','data-v9-sheet-close','aria-expanded'] as $needle)$check(str_contains($js,$needle),'JS missing '.$needle);
$core='';foreach(['app/Views/home.php','app/Views/layouts/header.php','app/Views/matches/show.php'] as $f)$core.=file_get_contents($root.'/'.$f)?:'';
foreach(['⚽','👋','🏆','💬','👥'] as $emoji)$check(!str_contains($core,$emoji),'Core v9 UI contains emoji '.$emoji);
if($fail){fwrite(STDERR,"FAIL v9_design_contract_test\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "PASS v9_design_contract_test\n";
