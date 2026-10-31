<?php

declare(strict_types=1);

$root=dirname(__DIR__);
spl_autoload_register(function(string $class) use($root): void {
    $prefix='App\\'; if(!str_starts_with($class,$prefix)) return;
    $path=$root.'/app/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(is_file($path)) require $path;
});
$checks=[];
$configFile=$root.'/config/app.php';
$config=is_file($configFile)?require $configFile:[];
$add=function(string $name,bool $ok,string $detail='') use (&$checks):void{$checks[]=[$name,$ok,$detail];};

$add('PHP 8.1+',PHP_VERSION_ID>=80100,PHP_VERSION);
$add('pdo_mysql',extension_loaded('pdo_mysql'),extension_loaded('pdo_mysql')?'aktif':'eksik');
$add('config/app.php',is_file($configFile),is_file($configFile)?'var':'config/app.example.php dosyasını kopyala');
$url=(string)($config['app']['url']??'');
$add('HTTPS APP URL',str_starts_with($url,'https://'),$url?:'tanımsız');
$add('Production debug kapalı',!(bool)($config['app']['debug']??true),(bool)($config['app']['debug']??true)?'debug=true':'debug=false');
$add('DB parolası değiştirildi',($config['database']['password']??'CHANGE_ME')!=='CHANGE_ME','');
$add('storage/logs yazılabilir',is_dir($root.'/storage/logs')&&is_writable($root.'/storage/logs'),'storage/logs');
$add('avatar klasörü yazılabilir',is_dir($root.'/public/uploads/avatars')&&is_writable($root.'/public/uploads/avatars'),'public/uploads/avatars');
$add('Front controller',is_file($root.'/public/index.php')&&is_readable($root.'/public/index.php'),'public/index.php');
$hotfixCss=$root.'/public/assets/css/app-v108.css';
$add('CSS kaynak dosyası',is_file($root.'/public/assets/css/app.css')&&filesize($root.'/public/assets/css/app.css')>10000,'public/assets/css/app.css');
$add('CSS dosyası',is_file($hotfixCss)&&filesize($hotfixCss)>10000,'public/assets/css/app-v108.css');
$add('JS dosyası',is_file($root.'/public/assets/js/app.js')&&filesize($root.'/public/assets/js/app.js')>500,'public/assets/js/app.js');
$criticalViews=['app/Views/auth/login.php','app/Views/auth/register.php','app/Views/errors/403.php','app/Views/errors/404.php','app/Views/errors/419.php','app/Views/errors/429.php','app/Views/errors/500.php','app/Views/account/index.php','app/Views/team-finder/index.php','app/Views/admin/user-detail.php','app/Views/amateur-market/index.php','app/Views/amateur-market/form.php','app/Views/amateur-market/show.php','app/Views/admin/amateur-ads.php','app/Views/admin/amateur-ad-show.php','app/Views/news/index.php','app/Views/news/show.php','app/Views/admin/news.php','app/Views/admin/news-form.php','app/Views/referees/index.php','app/Views/referees/form.php','app/Views/referees/show.php','app/Views/coaches/index.php','app/Views/coaches/form.php','app/Views/coaches/show.php','app/Views/admin/referee-profile.php','app/Views/admin/coach-profile.php'];
$missingViews=[];foreach($criticalViews as $view){if(!is_file($root.'/'.$view))$missingViews[]=$view;}
$add('Kritik kullanıcı ekranları',$missingViews===[],$missingViews===[]?'tam':'eksik: '.implode(', ',$missingViews));
$add('Production repair scripti',is_file($root.'/scripts/production-repair.php'),'php scripts/production-repair.php --apply');
$add('Production smoke scripti',is_file($root.'/scripts/production-smoke.php'),'php scripts/production-smoke.php');
$css=(string)@file_get_contents($hotfixCss);
$js=(string)@file_get_contents($root.'/public/assets/js/app.js');
$add('V10.3 responsive katmanı',str_contains($css,'TAKIMBUL V10.3 FINAL LAUNCH')&&str_contains($css,'@media(max-width:430px)'),'public/assets/css/app-v108.css');
$add('V10.7.1 asset senkronu',str_contains($css,'TAKIMBUL V10.7.1 HOTFIX')&&str_contains((string)@file_get_contents($root.'/app/Views/layouts/header.php'),'app-v108.css'),'PHP markup + hotfix CSS eşleşiyor');
$add('V10.3 auth submit guard',str_contains($js,'data-submit-guard')&&str_contains($js,'is-submitting'),'public/assets/js/app.js');
$add('V10.3 429 hata ekranı',is_file($root.'/app/Views/errors/429.php'),'app/Views/errors/429.php');
$add('V10.3 browser smoke',is_file($root.'/tests/v103_browser_smoke.sh')&&is_file($root.'/tests/v103_browser_smoke.py'),'bash tests/v103_browser_smoke.sh');
$locationFile=$root.'/public/assets/data/turkey-locations.json';
$locationData=is_file($locationFile)?json_decode((string)file_get_contents($locationFile),true):null;
$locationProvinceCount=is_array($locationData)?count($locationData):0;$locationDistrictCount=0;if(is_array($locationData)){foreach($locationData as $districts){if(is_array($districts))$locationDistrictCount+=count($districts);}}
$add('V10.4 Türkiye konum verisi',$locationProvinceCount===81&&$locationDistrictCount>=970,$locationProvinceCount.' il / '.$locationDistrictCount.' ilçe');
$add('V10.4 bağlı ilçe seçimi',is_file($root.'/public/assets/js/location-selects.js')&&str_contains((string)@file_get_contents($root.'/public/assets/js/location-selects.js'),'data-location-province'),'public/assets/js/location-selects.js');
$add('V10.4 haber migration',is_file($root.'/database/migrations/2026-09-05-v104-news.sql'),'database/migrations/2026-09-05-v104-news.sql');
$add('V10.4 haber route',str_contains((string)@file_get_contents($root.'/public/index.php'),"/haberler")&&str_contains((string)@file_get_contents($root.'/public/index.php'),"/admin/haberler"),'public + admin');
$add('V10.4 favicon seti',is_file($root.'/public/favicon.svg')&&is_file($root.'/public/favicon.ico')&&is_file($root.'/public/favicon-32x32.png')&&is_file($root.'/public/apple-touch-icon.png'),'SVG + ICO + PNG + Apple');
$add('V10.6 club squad migration',is_file($root.'/database/migrations/2026-09-05-v106-club-squad.sql'),'database/migrations/2026-09-05-v106-club-squad.sql');
$add('V10.7 verification roles sponsored news migration',is_file($root.'/database/migrations/2026-09-05-v107-verification-roles-sponsored-news.sql'),'database/migrations/2026-09-05-v107-verification-roles-sponsored-news.sql');
$add('V10.8 football identities migration',is_file($root.'/database/migrations/2026-09-08-v108-football-identities.sql'),'database/migrations/2026-09-08-v108-football-identities.sql');
$add('V10.8 football identities asset senkronu',str_contains($css,'TAKIMBUL V10.8 FOOTBALL IDENTITIES')&&str_contains((string)@file_get_contents($root.'/app/Views/layouts/header.php'),'app-v108.css')&&str_contains((string)@file_get_contents($root.'/app/Views/layouts/admin-header.php'),'app-v108.css'),'public/admin markup + V10.8 CSS eşleşiyor');

$routes=(string)@file_get_contents($root.'/public/index.php');
$header=(string)@file_get_contents($root.'/app/Views/layouts/header.php');
$career=(string)@file_get_contents($root.'/app/Controllers/CareerController.php');
$removedAuthCall=!str_contains($header,'Auth::isAdmin')&&!str_contains($career,'Auth::isAdmin');
$add('Giriş sonrası kaldırılmış Auth çağrısı',$removedAuthCall,$removedAuthCall?'temiz':'Auth::isAdmin kalmış; v9.1.2 dosyalarını yeniden yükle');
$add('v9.1 ayrı admin login route',str_contains($routes,"/admin/giris"),'');
$add('v9.1 ayrı AdminAuth',is_file($root.'/app/Core/AdminAuth.php'),'app/Core/AdminAuth.php');
$add('v8.1 reklam önizleme route',str_contains($routes,"/admin/reklamlar/{id}/onizle"),'');
$add('v9 Müsaitim route',str_contains($routes,"/musaitim"),'');
$add('v9 Matchday katılım route',str_contains($routes,"/mac/{id}/katilim"),'');
$add('v9 Anlık Rakip route',str_contains($routes,"/rakip-bul/anlik"),'');
$add('v9 migration',is_file($root.'/database/migrations/2026-09-01-v9-matchday.sql'),'database/migrations/2026-09-01-v9-matchday.sql');
$add('v9.1 migration',is_file($root.'/database/migrations/2026-09-01-v91-home-ads-admin.sql'),'database/migrations/2026-09-01-v91-home-ads-admin.sql');
$add('v10 migration',is_file($root.'/database/migrations/2026-09-01-v10-ultimate-platform.sql'),'database/migrations/2026-09-01-v10-ultimate-platform.sql');
$add('v10 Hesabım route',str_contains($routes,"/hesabim"),'');
$add('v10 Takım Bul route',str_contains($routes,"/takim-bul"),'');
$add('v10 global arama route',str_contains($routes,"/ara"),'');
$add('v10 admin kullanıcı detayı',str_contains($routes,"/admin/kullanici/{id}"),'');
$add('v10.1 amatör futbolcu route',str_contains($routes,"/amator-futbolcular")&&str_contains($routes,"/amator-ilanim")&&str_contains($routes,"/amator-futbolcu/{slug}"),'');
$add('v10.1 admin amatör ilan route',str_contains($routes,"/admin/amator-ilanlar")&&str_contains($routes,"/admin/amator-ilan/{id}"),'');
$add('v10.1 migration',is_file($root.'/database/migrations/2026-09-02-v101-amateur-player-market.sql'),'database/migrations/2026-09-02-v101-amateur-player-market.sql');
$add('v10.2 oyuncu vitrini migration',is_file($root.'/database/migrations/2026-09-04-v102-player-showcase.sql'),'database/migrations/2026-09-04-v102-player-showcase.sql');
$adModel=(string)@file_get_contents($root.'/app/Models/Ad.php');
$add('v10 premium reklam slotları',str_contains($adModel,"'home_top'")&&str_contains($adModel,"'welcome_takeover'"),'home_top + welcome_takeover');
$add('v10.1 amatör reklam slotları',str_contains($adModel,"'amateur_list_top'")&&str_contains($adModel,"'amateur_list_inline'")&&str_contains($adModel,"'amateur_detail_inline'"),'3 amatör pazar slotu');
$add('robots route',str_contains($routes,"/robots.txt"),'');
$add('sitemap route',str_contains($routes,"/sitemap.xml"),'');
$add('OG image',is_file($root.'/public/assets/img/og-default.png'),'public/assets/img/og-default.png');
$verification=trim((string)($config['seo']['google_site_verification']??''));
$add('Search Console doğrulama kodu',$verification!=='',$verification!==''?'tanımlı':'yayın sonrası ekle');
$mailFrom=trim((string)($config['mail']['from']??''));
$add('Gönderici e-posta',$mailFrom!==''&&!str_contains($mailFrom,'CHANGE_ME'),$mailFrom?:'tanımsız');

$dbConnected=false;
if(extension_loaded('pdo_mysql')&&is_file($configFile)){
    try{
        $db=$config['database']??[];
        $dsn=sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',(string)($db['host']??'127.0.0.1'),(int)($db['port']??3306),(string)($db['database']??''));
        $pdo=new PDO($dsn,(string)($db['username']??''),(string)($db['password']??''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
        $dbConnected=(int)$pdo->query('SELECT 1')->fetchColumn()===1;
        $add('DB bağlantısı',$dbConnected,$dbConnected?'OK':'SELECT 1 başarısız');

        if($dbConnected){
            $requiredTables=['users','admins','player_profiles','teams','team_members','player_wanted_posts','player_wanted_applications','opponent_posts','match_offers','seasons','matches','match_players','match_lineups','match_player_ratings','player_match_stats','career_highlights','transfer_offers','scout_accounts','scout_watchlists','scout_invites','user_tokens','auth_rate_limits','reports','notifications','direct_threads','direct_messages','player_availabilities','availability_invites','match_attendance','player_videos','site_settings','ads','admin_user_notes','team_search_profiles','team_invites','amateur_player_ads','amateur_player_career_entries','amateur_player_ad_events','amateur_player_favorites','news','team_formations','news_sponsors','admin_audit_log','user_football_identities','referee_profiles','coach_profiles','coach_career_entries','team_staff_memberships','referee_match_assignments'];
            $tableStmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:name');
            $missingTables=[];
            foreach($requiredTables as $table){$tableStmt->execute(['name'=>$table]);if((int)$tableStmt->fetchColumn()!==1)$missingTables[]=$table;}
            $add('Zorunlu DB tabloları',$missingTables===[],$missingTables===[]?'tam':'eksik: '.implode(', ',$missingTables));

            $requiredColumns=[
                ['users','username'],['users','email_verified_at'],['users','last_seen_at'],['users','phone'],['users','privacy_accepted_at'],
                ['player_profiles','avatar_path'],['player_profiles','visibility'],
                ['site_settings','updated_by_admin_id'],['scout_accounts','reviewed_by_admin_id'],['teams','short_name'],['teams','primary_color'],['teams','secondary_color'],['teams','home_venue'],['teams','instagram_url'],['team_members','team_position'],['team_members','squad_status'],['amateur_player_ads','status'],['amateur_player_ads','profile_intent'],['amateur_player_ads','featured_until'],['amateur_player_career_entries','verification_status'],['admins','role'],['users','profile_verified_at'],['users','profile_verified_by_admin_id'],['news','sponsor_id'],['news','sponsor_cta_text'],['news','sponsor_cta_url'],['user_football_identities','identity_type'],['referee_profiles','license_status'],['referee_profiles','availability_status'],['coach_profiles','license_status'],['coach_profiles','intent'],['team_staff_memberships','staff_role'],['referee_match_assignments','status'],
            ];
            $columnStmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table AND COLUMN_NAME=:column');
            $missingColumns=[];
            foreach($requiredColumns as [$table,$column]){$columnStmt->execute(['table'=>$table,'column'=>$column]);if((int)$columnStmt->fetchColumn()!==1)$missingColumns[]=$table.'.'.$column;}
            $add('Zorunlu DB kolonları',$missingColumns===[],$missingColumns===[]?'tam':'eksik: '.implode(', ',$missingColumns));

            $runtimeAudit=\App\Core\SchemaRepair::audit($pdo);
            $runtimeIssues=[];
            foreach(['missing_tables'=>'tablo','missing_columns'=>'kolon','missing_indexes'=>'index','enum_mismatches'=>'enum'] as $auditKey=>$label){if(($runtimeAudit[$auditKey]??[])!==[])$runtimeIssues[]=$label.': '.implode(', ',$runtimeAudit[$auditKey]);}
            if(!($runtimeAudit['admin_ready']??false))$runtimeIssues[]='ayrı admin eksik';
            if(!($runtimeAudit['email_verification_off']??false))$runtimeIssues[]='e-posta doğrulaması kapalı değil';
            $runtimeDetail=($runtimeAudit['ok']??false)?'tam':'php scripts/production-repair.php --apply | '.implode(' | ',$runtimeIssues);
            $add('DB runtime şeması',(bool)($runtimeAudit['ok']??false),$runtimeDetail);

            $adminReady=false;
            if(!in_array('admins',$missingTables,true)){
                $adminStmt=$pdo->prepare('SELECT COUNT(*) FROM admins WHERE username=:username AND status="active"');
                $adminStmt->execute(['username'=>'veribayt']);
                $adminReady=(int)$adminStmt->fetchColumn()===1;
            }
            $add('Ayrı admin hesabı',$adminReady,$adminReady?'veribayt aktif':'admins tablosu/migration kontrol edilmeli');

            $verificationOff=true;
            if(!in_array('site_settings',$missingTables,true)){
                $setting=$pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key=:key LIMIT 1');
                $setting->execute(['key'=>'require_email_verification']);
                $value=$setting->fetchColumn();
                $verificationOff=$value===false || (string)$value==='0';
            }
            $add('E-posta doğrulaması geçici kapalı',$verificationOff,$verificationOff?'kapalı':'aktif');

            $welcomeEnabled=true;
            if(!in_array('site_settings',$missingTables,true)){
                $welcomeSetting=$pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key=:key LIMIT 1');
                $welcomeSetting->execute(['key'=>'welcome_ad_enabled']);
                $welcomeValue=$welcomeSetting->fetchColumn();
                $welcomeEnabled=$welcomeValue===false || (string)$welcomeValue==='1';
            }
            $add('İlk ziyaret reklam özelliği',$welcomeEnabled,$welcomeEnabled?'aktif':'admin ayarlarından kapalı');

            $firstScreenAds=0;
            if(!in_array('ads',$missingTables,true)){
                $firstScreenAds=(int)$pdo->query('SELECT COUNT(*) FROM ads WHERE status="active" AND starts_at<=NOW() AND ends_at>=NOW() AND placement IN ("home_top","welcome_takeover")')->fetchColumn();
            }
            $add('İlk ekran reklam kampanyası',$firstScreenAds>0,$firstScreenAds>0?$firstScreenAds.' aktif kampanya':'home_top veya welcome_takeover slotunda aktif reklam oluştur');
        }
    }catch(Throwable $e){
        $add('DB bağlantısı',false,$e->getMessage());
        $add('Zorunlu DB tabloları',false,'DB bağlantısı kurulamadı');
        $add('Zorunlu DB kolonları',false,'DB bağlantısı kurulamadı');
        $add('Ayrı admin hesabı',false,'DB bağlantısı kurulamadı');
    }
}else{
    $add('DB bağlantısı',false,'pdo_mysql veya config/app.php eksik');
    $add('Zorunlu DB tabloları',false,'DB kontrolü yapılamadı');
    $add('Zorunlu DB kolonları',false,'DB kontrolü yapılamadı');
    $add('Ayrı admin hesabı',false,'DB kontrolü yapılamadı');
}

foreach($checks as [$name,$ok,$detail]) printf("%s %-38s %s\n",$ok?'[OK]':'[!!]',$name,$detail);
$criticalNames=['PHP 8.1+','pdo_mysql','config/app.php','HTTPS APP URL','Production debug kapalı','DB parolası değiştirildi','storage/logs yazılabilir','avatar klasörü yazılabilir','Front controller','CSS dosyası','JS dosyası','Kritik kullanıcı ekranları','Giriş sonrası kaldırılmış Auth çağrısı','DB bağlantısı','Zorunlu DB tabloları','Zorunlu DB kolonları','DB runtime şeması','Ayrı admin hesabı','Production repair scripti','Production smoke scripti','V10.3 responsive katmanı','V10.3 auth submit guard','V10.3 429 hata ekranı','V10.3 browser smoke','V10.6 club squad migration','V10.7.1 asset senkronu','V10.8 football identities asset senkronu'];
$critical=array_filter($checks,fn($c)=>!$c[1]&&in_array($c[0],$criticalNames,true));
exit($critical?1:0);
