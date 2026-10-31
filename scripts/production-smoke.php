<?php

declare(strict_types=1);

if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__);
spl_autoload_register(function(string $class) use($root): void {$prefix='App\\';if(!str_starts_with($class,$prefix))return;$path=$root.'/app/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';if(is_file($path))require $path;});

use App\Core\Database;
use App\Core\SchemaRepair;

try{$pdo=Database::connection();}catch(Throwable $e){fwrite(STDERR,"[!!] DB bağlantısı: {$e->getMessage()}\n");exit(2);}
$audit=SchemaRepair::audit($pdo);
if(!($audit['ok']??false)){fwrite(STDERR,"[!!] DB runtime şeması hazır değil. Önce php scripts/production-repair.php --apply\n");exit(1);}

echo "[OK] DB bağlantısı\n[OK] DB runtime şeması\n";
$readTables=['users','player_profiles','teams','team_members','matches','direct_messages','notifications','player_availabilities','availability_invites','match_attendance','player_videos','site_settings','ads','team_search_profiles','team_invites','amateur_player_ads','amateur_player_career_entries','amateur_player_ad_events','amateur_player_favorites','admins','news','team_formations','news_sponsors','admin_audit_log','user_football_identities','referee_profiles','coach_profiles','coach_career_entries','team_staff_memberships','referee_match_assignments'];
try{foreach($readTables as $table){$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();}echo "[OK] Kritik modül okuma sorguları\n";}catch(Throwable $e){fwrite(STDERR,"[!!] Okuma smoke: {$e->getMessage()}\n");exit(1);}

$pdo->beginTransaction();
try{
    $suffix=date('YmdHis').bin2hex(random_bytes(3));$username='smoke_'.substr($suffix,-18);$email=$username.'@invalid.local';$phone='+90555'.str_pad((string)random_int(0,9999999),7,'0',STR_PAD_LEFT);
    $stmt=$pdo->prepare('INSERT INTO users (email,phone,privacy_accepted_at,password_hash,display_name,username,province,district,role,status,email_verified_at,last_seen_at) VALUES (:email,:phone,NOW(),:hash,:name,:username,:province,:district,"player","active",NOW(),NOW())');
    $stmt->execute(['email'=>$email,'phone'=>$phone,'hash'=>password_hash(bin2hex(random_bytes(12)),PASSWORD_DEFAULT),'name'=>'Production Smoke','username'=>$username,'province'=>'Antalya','district'=>'Alanya']);$uid=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO player_profiles (user_id,primary_position,visibility) VALUES (:u,"Orta Saha","private")')->execute(['u'=>$uid]);
    $pdo->prepare('INSERT INTO user_football_identities (user_id,identity_type,status,is_primary,completed_at) VALUES (:u,"recreational_player","active",1,NOW()),(:u2,"referee","draft",0,NULL),(:u3,"coach","draft",0,NULL)')->execute(['u'=>$uid,'u2'=>$uid,'u3'=>$uid]);
    $pdo->prepare('INSERT INTO referee_profiles (user_id,slug,province,district,status) VALUES (:u,:slug,"Antalya","Alanya","draft")')->execute(['u'=>$uid,'slug'=>'production-smoke-referee-'.$uid]);
    $pdo->prepare('INSERT INTO coach_profiles (user_id,slug,public_title,province,district,status) VALUES (:u,:slug,"Teknik Direktör","Antalya","Alanya","draft")')->execute(['u'=>$uid,'slug'=>'production-smoke-coach-'.$uid]);
    $pdo->prepare('INSERT INTO player_availabilities (user_id,starts_at,ends_at,province,district,position,radius_km,note,status) VALUES (:u,NOW(),DATE_ADD(NOW(),INTERVAL 2 HOUR),"Antalya","Alanya","Orta Saha",15,"production smoke","active")')->execute(['u'=>$uid]);
    $pdo->prepare('INSERT INTO team_search_profiles (user_id,province,district,primary_position,status) VALUES (:u,"Antalya","Alanya","Orta Saha","active")')->execute(['u'=>$uid]);
    $slug='production-smoke-'.$uid.'-'.substr($suffix,-6);
    $pdo->prepare('INSERT INTO amateur_player_ads (user_id,slug,province,district,primary_position,profile_intent,birth_year,license_status,desired_levels,contact_preference,whatsapp_public,status) VALUES (:u,:slug,"Antalya","Alanya","Orta Saha","showcase",2000,"unknown",:levels,"takimbul",0,"draft")')->execute(['u'=>$uid,'slug'=>$slug,'levels'=>json_encode([],JSON_UNESCAPED_UNICODE)]);$adId=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO amateur_player_career_entries (ad_id,season,club_name,level,position,matches,goals,assists,verification_status,sort_order) VALUES (:a,"2025/26","Production Smoke FK","1. Amatör","Orta Saha",1,0,0,"self_declared",1)')->execute(['a'=>$adId]);
    $pdo->prepare('INSERT INTO ads (type,advertiser_name,title,placement,device_target,starts_at,ends_at,status) VALUES ("manual","Production Smoke","Production Smoke","amateur_list_top","all",NOW(),DATE_ADD(NOW(),INTERVAL 1 HOUR),"draft")')->execute();
    $pdo->prepare('INSERT INTO news (title,slug,summary,body,category,status,is_featured) VALUES (:title,:slug,:summary,:body,"TakımBul","draft",0)')->execute(['title'=>'Production Smoke Haber','slug'=>'production-smoke-news-'.strtolower($suffix),'summary'=>'Production smoke','body'=>'Production smoke transactional news write.']);
    $teamSlug='production-smoke-team-'.strtolower($suffix);
    $pdo->prepare('INSERT INTO teams (name,slug,short_name,primary_color,secondary_color,home_venue,province,district,captain_user_id,status) VALUES ("Production Smoke FC",:slug,"PSF","#1F8F4E","#111111","Production Smoke Sahası","Antalya","Alanya",:captain,"active")')->execute(['slug'=>$teamSlug,'captain'=>$uid]);$teamId=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO team_members (team_id,user_id,role,status,jersey_number,team_position,squad_status) VALUES (:team,:user,"captain","active",10,"Orta Saha","starter")')->execute(['team'=>$teamId,'user'=>$uid]);
    $formationSlots=json_encode([["id"=>"gk","label"=>"GK","x"=>50,"y"=>88,"user_id"=>$uid]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $pdo->prepare('INSERT INTO team_formations (team_id,match_format,formation_code,slots_json,updated_by_user_id) VALUES (:team,"5v5","1-2-1",:slots,:user)')->execute(['team'=>$teamId,'slots'=>$formationSlots,'user'=>$uid]);
    $sponsorSlug='production-smoke-sponsor-'.strtolower($suffix);
    $pdo->prepare('INSERT INTO news_sponsors (name,slug,website_url,status) VALUES ("Production Smoke Sponsor",:slug,"https://example.com","active")')->execute(['slug'=>$sponsorSlug]);$sponsorId=(int)$pdo->lastInsertId();
    $adminId=(int)$pdo->query('SELECT id FROM admins WHERE status="active" ORDER BY id LIMIT 1')->fetchColumn();
    if($adminId>0){$pdo->prepare('INSERT INTO admin_audit_log (actor_admin_id,action,target_type,target_id,metadata_json) VALUES (:a,"production.smoke","sponsor",:t,:m)')->execute(['a'=>$adminId,'t'=>$sponsorId,'m'=>json_encode(['smoke'=>true])]);}
    $pdo->prepare('INSERT INTO site_settings (setting_key,setting_value) VALUES (:key,"1")')->execute(['key'=>'__production_smoke_'.$suffix]);
    echo "[OK] Transactional kayıt/profil/müsaitlik/amatör ilan/reklam/haber/kulüp/kadro/diziliş/sponsor/audit write-smoke\n";
    $pdo->rollBack();
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,"[!!] Write smoke: {$e->getMessage()}\n");exit(1);}

echo "[OK] Geçici smoke verileri rollback edildi\nProduction smoke PASS\n";
