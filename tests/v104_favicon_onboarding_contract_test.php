<?php
declare(strict_types=1);
function v104_must(bool $ok,string $message): void { if(!$ok){fwrite(STDERR,"FAIL v104_favicon_onboarding_contract_test\n- {$message}\n");exit(1);} }
$root=dirname(__DIR__);
$header=(string)file_get_contents($root.'/app/Views/layouts/header.php');
$admin=(string)file_get_contents($root.'/app/Views/layouts/admin-header.php');
$register=(string)file_get_contents($root.'/app/Views/auth/register.php');
$adminLogin=(string)file_get_contents($root.'/app/Views/admin/auth/login.php');
$error500=(string)file_get_contents($root.'/app/Views/errors/500.php');
$auth=(string)file_get_contents($root.'/app/Controllers/AuthController.php');
$index=(string)file_get_contents($root.'/public/index.php');
foreach(['public/favicon.svg','public/favicon.ico','public/apple-touch-icon.png','public/assets/js/location-selects.js'] as $file){v104_must(is_file($root.'/'.$file),'Missing V10.4 asset: '.$file);}
foreach([$header,$admin,$adminLogin,$error500] as $layout){v104_must(str_contains($layout,'rel="icon"'),'favicon metadata missing from layout.');v104_must(str_contains($layout,'apple-touch-icon'),'apple touch icon metadata missing from layout.');}
v104_must(str_contains($register,'password_confirmation'),'Registration password confirmation field missing.');
v104_must(str_contains($register,'data-location-province')&&str_contains($register,'data-location-district'),'Registration dependent location selects missing.');
v104_must(str_contains($auth,'FootballIdentity::nextOnboardingPath'),'New registration must redirect through football-identity onboarding.');
$identity=(string)file_get_contents($root.'/app/Models/FootballIdentity.php');
foreach(['/baslangic','/amator-ilanim','/hakem-profilim','/teknik-direktor-profilim','/takim-olustur'] as $path){v104_must(str_contains($identity,$path),'Football identity onboarding path missing: '.$path);}
v104_must(str_contains($index,"'/baslangic'"),'Onboarding route missing.');
echo "PASS v104_favicon_onboarding_contract_test\n";
