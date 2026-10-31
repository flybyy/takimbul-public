<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\TurkeyLocation;
use PDO;
use RuntimeException;
use Throwable;

final class AmateurPlayerAd
{
    public const STATUSES=['draft','pending','active','paused','found_team','rejected','expired'];
    public const LICENSE_STATUSES=['active','free','unlicensed','unknown'];
    public const CONTACT_PREFERENCES=['takimbul','whatsapp','both'];
    public const PROFILE_INTENTS=['team_search','showcase','both'];
    public const DESIRED_LEVELS=['BAL','Süper Amatör','1. Amatör','2. Amatör','U19','U18','U17','Akademi','Yerel Lig','Diğer'];
    public const SORTS=['newest','updated','complete'];

    public static function validateInput(array $data): array
    {
        $errors=[];$year=(int)date('Y');
        $province=trim((string)($data['province']??''));$district=trim((string)($data['district']??''));
        $primary=trim((string)($data['primary_position']??''));$secondary=trim((string)($data['secondary_position']??''));
        $birth=trim((string)($data['birth_year']??''));$height=trim((string)($data['height_cm']??''));$foot=trim((string)($data['dominant_foot']??''));
        $license=trim((string)($data['license_status']??'unknown'));$contact=trim((string)($data['contact_preference']??'takimbul'));$intent=trim((string)($data['profile_intent']??'team_search'));
        if($province===''||TurkeyLocation::canonicalProvince($province)===null)$errors['province']='Geçerli bir il seç.';
        elseif($district!==''&&!TurkeyLocation::isValidPair($province,$district))$errors['district']='Seçtiğin ilçe bu ile ait değil.';
        if(!in_array($primary,PlayerProfile::POSITIONS,true))$errors['primary_position']='Ana mevki seçmelisin.';
        if($secondary!==''&&!in_array($secondary,PlayerProfile::POSITIONS,true))$errors['secondary_position']='İkinci mevki geçersiz.';
        if($secondary!==''&&$secondary===$primary)$errors['secondary_position']='İkinci mevki ana mevkiden farklı olmalı.';
        if($birth===''||!ctype_digit($birth)||(int)$birth<$year-60||(int)$birth>$year-14)$errors['birth_year']='Doğum yılı geçersiz.';
        if($height!==''&&(!ctype_digit($height)||(int)$height<140||(int)$height>220))$errors['height_cm']='Boy 140-220 cm arasında olmalı.';
        if($foot!==''&&!in_array($foot,PlayerProfile::FEET,true))$errors['dominant_foot']='Baskın ayak geçersiz.';
        if(!in_array($license,self::LICENSE_STATUSES,true))$errors['license_status']='Lisans durumu geçersiz.';
        if(!in_array($contact,self::CONTACT_PREFERENCES,true))$errors['contact_preference']='İletişim tercihi geçersiz.';
        if(!in_array($intent,self::PROFILE_INTENTS,true))$errors['profile_intent']='Profil amacı geçersiz.';
        $levels=self::normalizeList($data['desired_levels']??[]);
        if(in_array($intent,['team_search','both'],true)&&$levels===[])$errors['desired_levels']='Takım arıyorsan en az bir hedef seviye seçmelisin.';
        foreach($levels as $level)if(!in_array($level,self::DESIRED_LEVELS,true)){$errors['desired_levels']='Hedef seviye seçeneklerinden biri geçersiz.';break;}
        foreach(['last_club'=>140,'football_story'=>1200,'availability_text'=>500] as $key=>$limit)if(self::length(trim((string)($data[$key]??'')))>$limit)$errors[$key]='Bu alan en fazla '.$limit.' karakter olabilir.';
        $cities=is_array($data['preferred_cities']??null)?self::normalizeList($data['preferred_cities']):array_values(array_filter(array_map('trim',explode(',',(string)($data['preferred_cities']??'')))));
        if(count($cities)>12)$errors['preferred_cities']='En fazla 12 şehir ekleyebilirsin.';
        foreach($cities as $city)if(self::length($city)>80){$errors['preferred_cities']='Şehir adları en fazla 80 karakter olabilir.';break;}
        $video=trim((string)($data['video_url']??''));if($video!==''&&self::safeVideoUrl($video)===null)$errors['video_url']='Video bağlantısı YouTube, Instagram veya TikTok üzerinden https olmalı.';
        return $errors;
    }

    public static function normalizeList(mixed $value): array
    {
        if(is_string($value)){$decoded=json_decode($value,true);$value=is_array($decoded)?$decoded:[$value];}
        if(!is_array($value))return[];
        $out=[];foreach($value as $item){$v=trim((string)$item);if($v!==''&&!in_array($v,$out,true))$out[]=$v;}return array_slice($out,0,20);
    }

    public static function safeVideoUrl(string $url): ?string
    {
        $url=trim($url);$parts=parse_url($url);if(!is_array($parts)||strtolower((string)($parts['scheme']??''))!=='https')return null;
        $host=strtolower((string)($parts['host']??''));$host=preg_replace('/^www\./','',$host)??$host;
        foreach(['youtube.com','youtu.be','instagram.com','tiktok.com'] as $allowed)if($host===$allowed||str_ends_with($host,'.'.$allowed))return $url;
        return null;
    }

    public static function expiresAt(string $publishedAt): string
    {
        $ts=strtotime($publishedAt);if($ts===false)$ts=time();return date('Y-m-d H:i:s',strtotime('+30 days',$ts));
    }

    public static function ageFromBirthYear(?int $birthYear): ?int
    {
        if(!$birthYear)return null;$age=(int)date('Y')-$birthYear;return $age>=0?$age:null;
    }

    public static function canExposeWhatsapp(array $ad,?int $age): bool
    {
        if($age!==null&&$age<18)return false;
        return !empty($ad['whatsapp_public'])&&in_array((string)($ad['contact_preference']??'takimbul'),['whatsapp','both'],true);
    }

    public static function isTransferOpen(array $ad): bool
    {
        return in_array((string)($ad['profile_intent']??'team_search'),['team_search','both'],true)&&(string)($ad['status']??'')==='active';
    }

    public static function makeSlug(string $displayName,int $userId): string
    {
        $map=['İ'=>'i','I'=>'i','ı'=>'i','Ş'=>'s','ş'=>'s','Ğ'=>'g','ğ'=>'g','Ü'=>'u','ü'=>'u','Ö'=>'o','ö'=>'o','Ç'=>'c','ç'=>'c'];
        $slug=strtolower(strtr(trim($displayName),$map));$slug=preg_replace('/[^a-z0-9]+/','-',$slug)??'';$slug=trim($slug,'-');if($slug==='')$slug='futbolcu';return substr($slug,0,120).'-'.$userId;
    }

    public static function expireStale(): int
    {
        try{
            $q=Database::connection()->prepare('UPDATE amateur_player_ads SET status=CASE WHEN profile_intent IN ("showcase","both") THEN "active" ELSE "expired" END,profile_intent=CASE WHEN profile_intent="both" THEN "showcase" ELSE profile_intent END,expires_at=CASE WHEN profile_intent IN ("showcase","both") THEN NULL ELSE expires_at END,updated_at=NOW() WHERE status="active" AND expires_at IS NOT NULL AND expires_at<NOW()');
            $q->execute();
            return $q->rowCount();
        }catch(Throwable){return 0;}
    }

    public static function forOwner(int $userId): ?array
    {
        self::expireStale();$s=Database::connection()->prepare(self::baseSelect().' WHERE a.user_id=:user_id LIMIT 1');$s->execute(['user_id'=>$userId]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!is_array($r))return null;$r['desired_levels']=self::decodeJsonList($r['desired_levels']??null);$r['preferred_cities']=self::decodeJsonList($r['preferred_cities']??null);$r['career']=AmateurCareerEntry::forAd((int)$r['id']);$r['career_summary']=AmateurCareerEntry::summary($r['career']);return$r;
    }

    public static function save(int $userId,array $data,array $careerRows,bool $submit): int
    {
        $errors=self::validateInput($data)+AmateurCareerEntry::validateRows($careerRows);if($errors!==[])throw new RuntimeException((string)reset($errors));
        $pdo=Database::connection();$pdo->beginTransaction();
        try{
            $identity=User::profileIdentity($userId);if(!$identity||($identity['status']??'')!=='active')throw new RuntimeException('Aktif kullanıcı hesabı bulunamadı.');
            $existing=self::forOwnerWithin($pdo,$userId);$slug=$existing['slug']??self::makeSlug((string)$identity['display_name'],$userId);$status=$submit?'pending':'draft';
            $payload=self::payload($data);
            if($existing){
                $sql='UPDATE amateur_player_ads SET slug=:slug,province=:province,district=:district,primary_position=:primary_position,profile_intent=:profile_intent,secondary_position=:secondary_position,birth_year=:birth_year,height_cm=:height_cm,dominant_foot=:dominant_foot,license_status=:license_status,desired_levels=:desired_levels,last_club=:last_club,football_story=:football_story,preferred_cities=:preferred_cities,availability_text=:availability_text,contact_preference=:contact_preference,whatsapp_public=:whatsapp_public,video_url=:video_url,status=:status,moderation_note=NULL,found_team_at=NULL,updated_at=NOW() WHERE id=:id AND user_id=:user_id';
                $params=$payload+['slug'=>$slug,'status'=>$status,'id'=>(int)$existing['id'],'user_id'=>$userId];$st=$pdo->prepare($sql);$st->execute($params);$adId=(int)$existing['id'];
            }else{
                $sql='INSERT INTO amateur_player_ads (user_id,slug,province,district,primary_position,profile_intent,secondary_position,birth_year,height_cm,dominant_foot,license_status,desired_levels,last_club,football_story,preferred_cities,availability_text,contact_preference,whatsapp_public,video_url,status) VALUES (:user_id,:slug,:province,:district,:primary_position,:profile_intent,:secondary_position,:birth_year,:height_cm,:dominant_foot,:license_status,:desired_levels,:last_club,:football_story,:preferred_cities,:availability_text,:contact_preference,:whatsapp_public,:video_url,:status)';
                $params=$payload+['user_id'=>$userId,'slug'=>$slug,'status'=>$status];$st=$pdo->prepare($sql);$st->execute($params);$adId=(int)$pdo->lastInsertId();
            }
            AmateurCareerEntry::replace($pdo,$adId,$careerRows);self::recordEventWithPdo($pdo,$adId,$submit?'submitted':'saved','user',$userId,null);$pdo->commit();return$adId;
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw$e;}
    }

    public static function publicList(array $filters=[],int $page=1,int $limit=24): array
    {
        self::expireStale();$page=max(1,$page);$limit=max(1,min(60,$limit));$offset=($page-1)*$limit;
        $where=['a.status="active"','(a.profile_intent="showcase" OR a.expires_at>=NOW())','u.status="active"'];$params=[];
        foreach(['province','district','license_status','profile_intent'] as $key){$v=trim((string)($filters[$key]??''));if($v!==''){$where[]='a.'.$key.'=:'.$key;$params[$key]=$v;}}
        $pos=trim((string)($filters['position']??''));if($pos!==''){$where[]='(a.primary_position=:position_primary OR a.secondary_position=:position_secondary)';$params['position_primary']=$pos;$params['position_secondary']=$pos;}
        $foot=trim((string)($filters['dominant_foot']??''));if($foot!==''){$where[]='a.dominant_foot=:dominant_foot';$params['dominant_foot']=$foot;}
        $level=trim((string)($filters['level']??''));if($level!==''){$where[]='JSON_CONTAINS(a.desired_levels,JSON_QUOTE(:desired_level))';$params['desired_level']=$level;}
        $year=(int)date('Y');$ageMin=(int)($filters['age_min']??0);$ageMax=(int)($filters['age_max']??0);if($ageMin>0){$where[]='a.birth_year<=:birth_year_max';$params['birth_year_max']=$year-$ageMin;}if($ageMax>0){$where[]='a.birth_year>=:birth_year_min';$params['birth_year_min']=$year-$ageMax;}
        $days=(int)($filters['days']??0);if(in_array($days,[7,14,30],true)){$where[]='a.published_at>=DATE_SUB(NOW(),INTERVAL '.$days.' DAY)';}
        if(!empty($filters['video']))$where[]='a.video_url IS NOT NULL AND a.video_url<>""';
        $order='a.published_at DESC,a.updated_at DESC';$sort=(string)($filters['sort']??'newest');if($sort==='updated')$order='a.updated_at DESC,a.published_at DESC';elseif($sort==='complete')$order='(CASE WHEN a.video_url IS NOT NULL THEN 1 ELSE 0 END + CASE WHEN a.last_club IS NOT NULL THEN 1 ELSE 0 END + (SELECT COUNT(*) FROM amateur_player_career_entries c WHERE c.ad_id=a.id)) DESC,a.published_at DESC';
        $sql=self::baseSelect().' WHERE '.implode(' AND ',$where).' ORDER BY '.$order.' LIMIT '.$limit.' OFFSET '.$offset;$q=Database::connection()->prepare($sql);$q->execute($params);$rows=$q->fetchAll(PDO::FETCH_ASSOC)?:[];foreach($rows as &$row){self::hydrateLists($row);$row['career_summary']=['matches'=>(int)$row['declared_matches'],'goals'=>(int)$row['declared_goals'],'assists'=>(int)$row['declared_assists']];}unset($row);return$rows;
    }

    public static function recentActive(int $limit=4): array{return self::publicList([],1,max(1,min(12,$limit)));}

    public static function findPublicBySlug(string $slug): ?array
    {
        self::expireStale();$s=Database::connection()->prepare(self::baseSelect().' WHERE a.slug=:slug AND a.status IN ("active","found_team","expired") AND u.status="active" LIMIT 1');$s->execute(['slug'=>$slug]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!is_array($r))return null;self::hydrateLists($r);$r['career']=AmateurCareerEntry::forAd((int)$r['id']);$r['career_summary']=AmateurCareerEntry::summary($r['career']);return$r;
    }

    public static function findPublicById(int $id): ?array
    {
        $s=Database::connection()->prepare(self::baseSelect().' WHERE a.id=:id AND a.status="active" AND (a.profile_intent="showcase" OR a.expires_at>=NOW()) AND u.status="active" LIMIT 1');$s->execute(['id'=>$id]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!is_array($r))return null;self::hydrateLists($r);return$r;
    }

    public static function toggleFavorite(int $userId,int $adId): bool
    {
        $pdo=Database::connection();$check=$pdo->prepare('SELECT 1 FROM amateur_player_favorites WHERE user_id=:user_id AND ad_id=:ad_id LIMIT 1');$check->execute(['user_id'=>$userId,'ad_id'=>$adId]);if($check->fetchColumn()){$d=$pdo->prepare('DELETE FROM amateur_player_favorites WHERE user_id=:user_id AND ad_id=:ad_id');$d->execute(['user_id'=>$userId,'ad_id'=>$adId]);return false;}$i=$pdo->prepare('INSERT INTO amateur_player_favorites (user_id,ad_id) VALUES (:user_id,:ad_id)');$i->execute(['user_id'=>$userId,'ad_id'=>$adId]);self::recordEvent($adId,'favorite','user',$userId);return true;
    }

    public static function isFavorite(int $userId,int $adId): bool{$s=Database::connection()->prepare('SELECT 1 FROM amateur_player_favorites WHERE user_id=:user_id AND ad_id=:ad_id LIMIT 1');$s->execute(['user_id'=>$userId,'ad_id'=>$adId]);return(bool)$s->fetchColumn();}

    public static function pause(int $userId): void{self::ownerStatusChange($userId,['active','pending'],'paused','paused');}
    public static function markFoundTeam(int $userId): void
    {
        $pdo=Database::connection();$s=$pdo->prepare('SELECT id,status FROM amateur_player_ads WHERE user_id=:user_id LIMIT 1');$s->execute(['user_id'=>$userId]);$ad=$s->fetch(PDO::FETCH_ASSOC);if(!$ad)throw new RuntimeException('İlan bulunamadı.');if(!in_array((string)$ad['status'],['active','paused'],true))throw new RuntimeException('İlanın mevcut durumunda bu işlem yapılamaz.');$u=$pdo->prepare('UPDATE amateur_player_ads SET status="active",profile_intent="showcase",expires_at=NULL,found_team_at=NOW(),updated_at=NOW() WHERE id=:id');$u->execute(['id'=>(int)$ad['id']]);self::recordEvent((int)$ad['id'],'found_team','user',$userId);
    }
    public static function renew(int $userId): void
    {
        $s=Database::connection()->prepare('SELECT id,status,profile_intent FROM amateur_player_ads WHERE user_id=:user_id LIMIT 1');$s->execute(['user_id'=>$userId]);$ad=$s->fetch(PDO::FETCH_ASSOC);if(!$ad)throw new RuntimeException('Yenilenecek profil bulunamadı.');if(($ad['status']??'')==='rejected')throw new RuntimeException('Reddedilen profili yenilemeden önce düzenleyip tekrar göndermelisin.');$now=date('Y-m-d H:i:s');$intent=(string)($ad['profile_intent']??'team_search');$expires=$intent==='showcase'?null:self::expiresAt($now);$u=Database::connection()->prepare('UPDATE amateur_player_ads SET status="active",published_at=:published_at,expires_at=:expires_at,found_team_at=NULL,moderation_note=NULL,updated_at=NOW() WHERE id=:id');$u->execute(['published_at'=>$now,'expires_at'=>$expires,'id'=>(int)$ad['id']]);self::recordEvent((int)$ad['id'],'renewed','user',$userId);$message=$intent==='showcase'?'Oyuncu profilin yeniden yayında.':'Takım arama profilin 30 gün daha yayında.';Notification::create($userId,'amateur_ad_renewed','Oyuncu profilin yenilendi',$message,'/amator-ilanim');
    }

    public static function recordView(int $adId): void{self::recordEvent($adId,'view','system',null);}
    public static function recordContact(int $adId,int $userId): void{self::recordEvent($adId,'contact_click','user',$userId);}

    public static function adminList(array $filters=[]): array
    {
        self::expireStale();$where=['1=1'];$params=[];$q=trim((string)($filters['q']??''));if($q!==''){$where[]='(u.display_name LIKE :q_name OR u.username LIKE :q_username OR u.email LIKE :q_email OR u.phone LIKE :q_phone OR a.last_club LIKE :q_club)';$like='%'.$q.'%';$params=['q_name'=>$like,'q_username'=>$like,'q_email'=>$like,'q_phone'=>$like,'q_club'=>$like];}
        foreach(['status','province','license_status','profile_intent'] as $key){$v=trim((string)($filters[$key]??''));if($v!==''){$where[]='a.'.$key.'=:'.$key;$params[$key]=$v;}}
        $position=trim((string)($filters['position']??''));if($position!==''){$where[]='(a.primary_position=:pos1 OR a.secondary_position=:pos2)';$params['pos1']=$position;$params['pos2']=$position;}
        $sql=self::baseSelect().' WHERE '.implode(' AND ',$where).' ORDER BY FIELD(a.status,"pending","active","paused","rejected","expired","found_team","draft"),a.updated_at DESC LIMIT 250';$s=Database::connection()->prepare($sql);$s->execute($params);$rows=$s->fetchAll(PDO::FETCH_ASSOC)?:[];foreach($rows as &$row)self::hydrateLists($row);unset($row);return$rows;
    }

    public static function adminFind(int $id): ?array
    {
        $s=Database::connection()->prepare('SELECT a.*,u.display_name,u.username,u.phone,u.email,u.profile_verified_at,u.province user_province,u.district user_district,p.avatar_path,p.visibility,(SELECT COALESCE(SUM(c.matches),0) FROM amateur_player_career_entries c WHERE c.ad_id=a.id) declared_matches,(SELECT COALESCE(SUM(c.goals),0) FROM amateur_player_career_entries c WHERE c.ad_id=a.id) declared_goals,(SELECT COALESCE(SUM(c.assists),0) FROM amateur_player_career_entries c WHERE c.ad_id=a.id) declared_assists,(SELECT COUNT(*) FROM amateur_player_favorites f WHERE f.ad_id=a.id) favorite_count FROM amateur_player_ads a INNER JOIN users u ON u.id=a.user_id LEFT JOIN player_profiles p ON p.user_id=u.id WHERE a.id=:id LIMIT 1');$s->execute(['id'=>$id]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!is_array($r))return null;self::hydrateLists($r);$r['career']=AmateurCareerEntry::forAd($id);$events=Database::connection()->prepare('SELECT * FROM amateur_player_ad_events WHERE ad_id=:id ORDER BY created_at DESC LIMIT 80');$events->execute(['id'=>$id]);$r['events']=$events->fetchAll(PDO::FETCH_ASSOC)?:[];return$r;
    }

    public static function adminSetStatus(int $adId,string $status,int $adminId,?string $note=null): void
    {
        if(!in_array($status,['active','rejected','paused','expired'],true))throw new RuntimeException('Geçersiz moderasyon durumu.');
        $pdo=Database::connection();$pdo->beginTransaction();
        try{
            $s=$pdo->prepare('SELECT user_id,status,profile_intent FROM amateur_player_ads WHERE id=:id FOR UPDATE');$s->execute(['id'=>$adId]);$ad=$s->fetch(PDO::FETCH_ASSOC);if(!$ad)throw new RuntimeException('Oyuncu profili bulunamadı.');
            $published=null;$expires=null;$activeMarker=0;
            if($status==='active'){$activeMarker=1;$published=date('Y-m-d H:i:s');$intent=(string)($ad['profile_intent']??'team_search');$expires=$intent==='showcase'?null:self::expiresAt($published);}
            $u=$pdo->prepare('UPDATE amateur_player_ads SET status=:status,moderation_note=:note,published_at=CASE WHEN :active_marker=1 THEN :published_value ELSE published_at END,expires_at=CASE WHEN :expiry_marker=1 THEN :expires_value ELSE expires_at END,updated_at=NOW() WHERE id=:id');
            $u->execute(['status'=>$status,'note'=>self::nullEmpty($note),'active_marker'=>$activeMarker,'published_value'=>$published,'expiry_marker'=>$activeMarker,'expires_value'=>$expires,'id'=>$adId]);
            self::recordEventWithPdo($pdo,$adId,'admin_'.$status,'admin',$adminId,$note);$pdo->commit();
            $type=$status==='active'?'amateur_ad_approved':($status==='rejected'?'amateur_ad_rejected':'amateur_ad_moderated');$title=$status==='active'?'Oyuncu profilin yayında':($status==='rejected'?'Profilinin moderasyon sonucu':'Profil durumun güncellendi');Notification::create((int)$ad['user_id'],$type,$title,$note,'/amator-ilanim');
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw$e;}
    }

    public static function setFeatured(int $adId,int $days,int $adminId): void{$days=max(0,min(30,$days));$until=$days>0?date('Y-m-d H:i:s',strtotime('+'.$days.' days')):null;$s=Database::connection()->prepare('UPDATE amateur_player_ads SET featured_until=:until,updated_at=NOW() WHERE id=:id');$s->execute(['until'=>$until,'id'=>$adId]);self::recordEvent($adId,$days>0?'featured':'feature_removed','admin',$adminId);}

    public static function adminCounts(): array
    {
        try{$rows=Database::connection()->query('SELECT status,COUNT(*) total FROM amateur_player_ads GROUP BY status')->fetchAll(PDO::FETCH_ASSOC)?:[];$out=[];foreach($rows as $r)$out[(string)$r['status']]=(int)$r['total'];return$out;}catch(Throwable){return[];}
    }

    private static function ownerStatusChange(int $userId,array $allowed,string $next,string $event): void
    {
        $pdo=Database::connection();$s=$pdo->prepare('SELECT id,status FROM amateur_player_ads WHERE user_id=:user_id LIMIT 1');$s->execute(['user_id'=>$userId]);$ad=$s->fetch(PDO::FETCH_ASSOC);if(!$ad)throw new RuntimeException('İlan bulunamadı.');if(!in_array((string)$ad['status'],$allowed,true))throw new RuntimeException('İlanın mevcut durumunda bu işlem yapılamaz.');$fields=$next==='found_team'?',found_team_at=NOW()':'';$u=$pdo->prepare('UPDATE amateur_player_ads SET status=:status'.$fields.',updated_at=NOW() WHERE id=:id');$u->execute(['status'=>$next,'id'=>(int)$ad['id']]);self::recordEvent((int)$ad['id'],$event,'user',$userId);
    }

    private static function payload(array $data): array
    {
        $cities=is_array($data['preferred_cities']??null)?self::normalizeList($data['preferred_cities']):array_values(array_filter(array_map('trim',explode(',',(string)($data['preferred_cities']??'')))));
        return ['province'=>TurkeyLocation::canonicalProvince((string)$data['province'])??trim((string)$data['province']),'district'=>self::nullEmpty(TurkeyLocation::canonicalDistrict((string)$data['province'],(string)($data['district']??''))??($data['district']??null)),'primary_position'=>trim((string)$data['primary_position']),'profile_intent'=>in_array((string)($data['profile_intent']??''),self::PROFILE_INTENTS,true)?(string)$data['profile_intent']:'team_search','secondary_position'=>self::nullEmpty($data['secondary_position']??null),'birth_year'=>(int)$data['birth_year'],'height_cm'=>trim((string)($data['height_cm']??''))===''?null:(int)$data['height_cm'],'dominant_foot'=>self::nullEmpty($data['dominant_foot']??null),'license_status'=>(string)$data['license_status'],'desired_levels'=>json_encode(self::normalizeList($data['desired_levels']??[]),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'last_club'=>self::nullEmpty($data['last_club']??null),'football_story'=>self::nullEmpty(substr(trim((string)($data['football_story']??'')),0,1200)),'preferred_cities'=>json_encode($cities,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'availability_text'=>self::nullEmpty(substr(trim((string)($data['availability_text']??'')),0,500)),'contact_preference'=>in_array((string)($data['contact_preference']??''),self::CONTACT_PREFERENCES,true)?(string)$data['contact_preference']:'takimbul','whatsapp_public'=>!empty($data['whatsapp_public'])?1:0,'video_url'=>self::safeVideoUrl((string)($data['video_url']??''))];
    }

    private static function forOwnerWithin(PDO $pdo,int $userId): ?array{$s=$pdo->prepare('SELECT id,slug,status FROM amateur_player_ads WHERE user_id=:user_id LIMIT 1');$s->execute(['user_id'=>$userId]);$r=$s->fetch(PDO::FETCH_ASSOC);return is_array($r)?$r:null;}
    private static function baseSelect(): string{return 'SELECT a.*,u.display_name,u.username,u.profile_verified_at,u.province user_province,u.district user_district,p.avatar_path,p.visibility,(SELECT COALESCE(SUM(c.matches),0) FROM amateur_player_career_entries c WHERE c.ad_id=a.id) declared_matches,(SELECT COALESCE(SUM(c.goals),0) FROM amateur_player_career_entries c WHERE c.ad_id=a.id) declared_goals,(SELECT COALESCE(SUM(c.assists),0) FROM amateur_player_career_entries c WHERE c.ad_id=a.id) declared_assists,(SELECT COUNT(*) FROM amateur_player_favorites f WHERE f.ad_id=a.id) favorite_count FROM amateur_player_ads a INNER JOIN users u ON u.id=a.user_id LEFT JOIN player_profiles p ON p.user_id=u.id';}
    private static function hydrateLists(array &$row): void{$row['desired_levels']=self::decodeJsonList($row['desired_levels']??null);$row['preferred_cities']=self::decodeJsonList($row['preferred_cities']??null);}
    private static function decodeJsonList(mixed $value): array{if(is_array($value))return$value;$d=json_decode((string)$value,true);return is_array($d)?array_values(array_filter(array_map('strval',$d))):[];}
    private static function recordEvent(int $adId,string $type,string $actorType='system',?int $actorId=null,?string $meta=null): void{try{self::recordEventWithPdo(Database::connection(),$adId,$type,$actorType,$actorId,$meta);}catch(Throwable){}}
    private static function recordEventWithPdo(PDO $pdo,int $adId,string $type,string $actorType='system',?int $actorId=null,?string $meta=null): void{$s=$pdo->prepare('INSERT INTO amateur_player_ad_events (ad_id,event_type,actor_type,actor_id,meta_text) VALUES (:ad_id,:event_type,:actor_type,:actor_id,:meta_text)');$s->execute(['ad_id'=>$adId,'event_type'=>substr($type,0,80),'actor_type'=>in_array($actorType,['system','user','admin'],true)?$actorType:'system','actor_id'=>$actorId,'meta_text'=>$meta!==null?substr(trim($meta),0,500):null]);}
    private static function nullEmpty(mixed $value): ?string{$v=trim((string)$value);return$v===''?null:$v;}
    private static function length(string $value): int{return function_exists('mb_strlen')?mb_strlen($value,'UTF-8'):strlen($value);}

}
