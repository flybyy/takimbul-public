<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\TurkeyLocation;
use PDO;

final class Ad
{
    public const TYPES=['manual','external_code'];
    public const PLACEMENTS=['home_top','welcome_takeover','home_feed','home_sidebar','players_feed','opponents_feed','match_inline','team_inline','career_inline','mobile_feed','amateur_list_top','amateur_list_inline','amateur_detail_inline'];
    public const DEVICES=['all','desktop','mobile'];
    public const STATUSES=['draft','active','paused','ended'];

    public static function validateInput(array $data): array
    {
        $errors=[];
        $type=trim((string)($data['type']??'manual'));
        $advertiser=trim((string)($data['advertiser_name']??''));
        $title=trim((string)($data['title']??''));
        $body=trim((string)($data['body']??''));
        $target=trim((string)($data['target_url']??''));
        $image=trim((string)($data['image_url']??''));
        $external=(string)($data['external_code']??'');
        $placement=trim((string)($data['placement']??''));
        $device=trim((string)($data['device_target']??'all'));
        $status=trim((string)($data['status']??'draft'));
        $province=trim((string)($data['province']??''));
        $district=trim((string)($data['district']??''));
        $starts=trim((string)($data['starts_at']??''));
        $ends=trim((string)($data['ends_at']??''));

        if(!in_array($type,self::TYPES,true)) $errors['type']='Reklam türü geçersiz.';
        if($advertiser==='' || strlen($advertiser)>180) $errors['advertiser_name']='Reklamveren adı zorunlu ve en fazla 180 karakter olmalı.';
        if($title==='' || strlen($title)>180) $errors['title']='Reklam başlığı zorunlu ve en fazla 180 karakter olmalı.';
        if(strlen($body)>400) $errors['body']='Açıklama 400 karakteri geçemez.';
        if(!in_array($placement,self::PLACEMENTS,true)) $errors['placement']='Reklam alanı geçersiz.';
        if(!in_array($device,self::DEVICES,true)) $errors['device_target']='Cihaz hedefi geçersiz.';
        if(!in_array($status,self::STATUSES,true)) $errors['status']='Reklam durumu geçersiz.';
        if(strlen($province)>80) $errors['province']='İl adı çok uzun.';
        if(strlen($district)>80) $errors['district']='İlçe adı çok uzun.';
        if($province!=='' && TurkeyLocation::canonicalProvince($province)===null) $errors['province']='Geçerli bir il seçmelisin.';
        if($district!=='' && $province==='') $errors['district']='İlçe hedeflemek için il seçmelisin.';
        elseif($district!=='' && !TurkeyLocation::isValidPair($province,$district)) $errors['district']='Seçtiğin ilçe hedeflenen ile ait değil.';

        $startTs=$starts!==''?strtotime($starts):false;
        $endTs=$ends!==''?strtotime($ends):false;
        if($startTs===false) $errors['starts_at']='Başlangıç tarihi geçersiz.';
        if($endTs===false) $errors['ends_at']='Bitiş tarihi geçersiz.';
        if($startTs!==false && $endTs!==false && $endTs<=$startTs) $errors['ends_at']='Bitiş tarihi başlangıçtan sonra olmalı.';

        if($type==='manual'){
            if(self::safeTargetUrl($target)===null) $errors['target_url']='Hedef bağlantı geçerli bir http/https adresi olmalı.';
            if($image!=='' && !self::isSafeImageReference($image)) $errors['image_url']='Görsel yolu / ile başlamalı veya http/https adresi olmalı.';
        } elseif($type==='external_code' && trim($external)==='') {
            $errors['external_code']='Harici reklam kodu zorunlu.';
        }
        return $errors;
    }

    public static function safeTargetUrl(string $url): ?string
    {
        $url=trim($url);
        if($url==='' || str_starts_with($url,'//')) return null;
        $parts=parse_url($url);
        if(!is_array($parts)) return null;
        $scheme=strtolower((string)($parts['scheme']??''));
        $host=(string)($parts['host']??'');
        if(!in_array($scheme,['http','https'],true) || $host==='') return null;
        return $url;
    }

    public static function ctr(int $clicks,int $impressions): float
    {
        if($impressions<=0) return 0.0;
        return round(($clicks/$impressions)*100,2);
    }

    public static function isLive(array $ad,?int $now=null): bool
    {
        $now??=time();
        if((string)($ad['status']??'')!=='active') return false;
        $start=strtotime((string)($ad['starts_at']??''));
        $end=strtotime((string)($ad['ends_at']??''));
        return $start!==false && $end!==false && $now>=$start && $now<=$end;
    }

    public static function eligibleForContext(array $ad,string $placement,string $province='',string $district='',string $device='desktop',?int $now=null): bool
    {
        $now??=time();
        if(!self::isLive($ad,$now)) return false;
        if((string)($ad['placement']??'')!==$placement) return false;
        $deviceTarget=(string)($ad['device_target']??'all');
        if($deviceTarget!=='all' && $deviceTarget!==$device) return false;
        $adProvince=trim((string)($ad['province']??''));
        $adDistrict=trim((string)($ad['district']??''));
        if($adProvince!=='' && self::fold($adProvince)!==self::fold($province)) return false;
        if($adDistrict!=='' && self::fold($adDistrict)!==self::fold($district)) return false;
        return true;
    }

    public static function diagnose(array $ad, array $context=[]): array
    {
        $now=(int)($context['now'] ?? time());
        $status=(string)($ad['status'] ?? 'draft');
        $start=strtotime((string)($ad['starts_at'] ?? ''));
        $end=strtotime((string)($ad['ends_at'] ?? ''));
        if($status==='draft') return ['code'=>'draft','label'=>'Taslak','detail'=>'Reklam henüz aktif edilmedi.','ok'=>false];
        if($status==='paused') return ['code'=>'paused','label'=>'Duraklatıldı','detail'=>'Reklam yönetici tarafından duraklatılmış.','ok'=>false];
        if($status==='ended') return ['code'=>'ended','label'=>'Bitti','detail'=>'Kampanya manuel olarak bitirilmiş.','ok'=>false];
        if($start===false || $end===false) return ['code'=>'invalid_dates','label'=>'Tarih Hatası','detail'=>'Başlangıç veya bitiş tarihi okunamıyor.','ok'=>false];
        if($now<$start) return ['code'=>'scheduled','label'=>'Planlandı','detail'=>'Başlangıç tarihi henüz gelmedi.','ok'=>false];
        if($now>$end) return ['code'=>'expired','label'=>'Süresi Doldu','detail'=>'Kampanyanın bitiş tarihi geçmiş.','ok'=>false];
        $type=(string)($ad['type'] ?? 'manual');
        if($type==='manual' && self::safeTargetUrl((string)($ad['target_url']??''))===null) return ['code'=>'invalid_target','label'=>'Bağlantı Hatası','detail'=>'Manuel reklamın hedef bağlantısı geçersiz.','ok'=>false];
        if($type==='external_code' && trim((string)($ad['external_code']??''))==='') return ['code'=>'invalid_code','label'=>'Kod Eksik','detail'=>'Harici reklam kodu boş.','ok'=>false];
        $placement=(string)($context['placement'] ?? ($ad['placement'] ?? ''));
        if($placement!=='' && (string)($ad['placement']??'')!==$placement) return ['code'=>'placement_mismatch','label'=>'Slot Uyuşmuyor','detail'=>'Bu reklam farklı bir reklam alanına atanmış.','ok'=>false];
        $device=(string)($context['device'] ?? 'desktop');
        $target=(string)($ad['device_target'] ?? 'all');
        if($target!=='all' && $target!==$device) return ['code'=>'device_mismatch','label'=>'Cihaz Uyuşmuyor','detail'=>'Reklam '.$target.' cihazlara hedeflenmiş.','ok'=>false];
        $province=trim((string)($context['province']??''));
        $district=trim((string)($context['district']??''));
        $adProvince=trim((string)($ad['province']??''));
        $adDistrict=trim((string)($ad['district']??''));
        if($adProvince!=='' && self::fold($adProvince)!==self::fold($province)) return ['code'=>'province_mismatch','label'=>'İl Uyuşmuyor','detail'=>'Reklam yalnızca '.$adProvince.' hedefli.','ok'=>false];
        if($adDistrict!=='' && self::fold($adDistrict)!==self::fold($district)) return ['code'=>'district_mismatch','label'=>'İlçe Uyuşmuyor','detail'=>'Reklam yalnızca '.$adDistrict.' hedefli.','ok'=>false];
        return ['code'=>'live','label'=>'Yayında','detail'=>'Bu bağlamda reklam gösterime uygun.','ok'=>true];
    }

    public static function allForAdmin(): array
    {
        return Database::connection()->query('SELECT * FROM ads ORDER BY FIELD(status,"active","draft","paused","ended"),created_at DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt=Database::connection()->prepare('SELECT * FROM ads WHERE id=:id LIMIT 1');
        $stmt->execute(['id'=>$id]); $row=$stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row)?$row:null;
    }

    public static function create(array $data): int
    {
        $pdo=Database::connection();
        $stmt=$pdo->prepare('INSERT INTO ads (type,advertiser_name,title,body,image_url,target_url,external_code,placement,device_target,province,district,starts_at,ends_at,status) VALUES (:type,:advertiser_name,:title,:body,:image_url,:target_url,:external_code,:placement,:device_target,:province,:district,:starts_at,:ends_at,:status)');
        $stmt->execute(self::dbPayload($data));
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id,array $data): bool
    {
        $payload=self::dbPayload($data); $payload['id']=$id;
        $stmt=Database::connection()->prepare('UPDATE ads SET type=:type,advertiser_name=:advertiser_name,title=:title,body=:body,image_url=:image_url,target_url=:target_url,external_code=:external_code,placement=:placement,device_target=:device_target,province=:province,district=:district,starts_at=:starts_at,ends_at=:ends_at,status=:status,updated_at=NOW() WHERE id=:id');
        $stmt->execute($payload); return $stmt->rowCount()>=0;
    }

    public static function setStatus(int $id,string $status): bool
    {
        if(!in_array($status,self::STATUSES,true)) return false;
        $stmt=Database::connection()->prepare('UPDATE ads SET status=:status,updated_at=NOW() WHERE id=:id');
        $stmt->execute(['status'=>$status,'id'=>$id]); return $stmt->rowCount()===1;
    }

    public static function delete(int $id): bool
    {
        $stmt=Database::connection()->prepare('DELETE FROM ads WHERE id=:id');$stmt->execute(['id'=>$id]);return $stmt->rowCount()===1;
    }

    public static function pickForContext(string $placement,string $province='',string $district='',string $device='desktop'): ?array
    {
        if(!in_array($placement,self::PLACEMENTS,true)) return null;
        if(!in_array($device,['desktop','mobile'],true)) $device='desktop';
        // Location is intentionally NOT compared in SQL. MySQL collations and Turkish I/İ/ı
        // variants can reject an otherwise valid local campaign before PHP normalization runs.
        $stmt=Database::connection()->prepare('SELECT * FROM ads WHERE status="active" AND placement=:placement AND starts_at<=NOW() AND ends_at>=NOW() AND (device_target="all" OR device_target=:device) ORDER BY impressions ASC,id ASC');
        $stmt->execute(['placement'=>$placement,'device'=>$device]);
        $rows=$stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $eligible=[];
        foreach($rows as $row){
            if(self::eligibleForContext($row,$placement,$province,$district,$device)) $eligible[]=$row;
        }
        if($eligible===[]) return null;
        usort($eligible,static function(array $a,array $b): int {
            $specificity=self::locationSpecificity($b)<=>self::locationSpecificity($a);
            if($specificity!==0) return $specificity;
            $impressions=((int)($a['impressions']??0))<=>((int)($b['impressions']??0));
            return $impressions!==0?$impressions:((int)($a['id']??0)<=>((int)($b['id']??0)));
        });
        return $eligible[0];
    }

    public static function recordImpression(int $id): void
    {
        $stmt=Database::connection()->prepare('UPDATE ads SET impressions=impressions+1 WHERE id=:id AND status="active"');$stmt->execute(['id'=>$id]);
    }

    public static function recordClick(int $id): void
    {
        $stmt=Database::connection()->prepare('UPDATE ads SET clicks=clicks+1 WHERE id=:id');$stmt->execute(['id'=>$id]);
    }

    private static function dbPayload(array $data): array
    {
        $type=(string)($data['type']??'manual');
        return [
            'type'=>$type,
            'advertiser_name'=>trim((string)($data['advertiser_name']??'')),
            'title'=>trim((string)($data['title']??'')),
            'body'=>trim((string)($data['body']??''))?:null,
            'image_url'=>$type==='manual'?(trim((string)($data['image_url']??''))?:null):null,
            'target_url'=>$type==='manual'?self::safeTargetUrl((string)($data['target_url']??'')):null,
            'external_code'=>$type==='external_code'?(trim((string)($data['external_code']??''))?:null):null,
            'placement'=>(string)($data['placement']??'home_feed'),
            'device_target'=>(string)($data['device_target']??'all'),
            'province'=>($p=trim((string)($data['province']??'')))!==''?(TurkeyLocation::canonicalProvince($p)??$p):null,
            'district'=>($d=trim((string)($data['district']??'')))!==''?(TurkeyLocation::canonicalDistrict((string)($data['province']??''),$d)??$d):null,
            'starts_at'=>date('Y-m-d H:i:s',strtotime((string)($data['starts_at']??'now'))?:time()),
            'ends_at'=>date('Y-m-d H:i:s',strtotime((string)($data['ends_at']??'+30 days'))?:strtotime('+30 days')),
            'status'=>(string)($data['status']??'draft'),
        ];
    }

    private static function isSafeImageReference(string $image): bool
    {
        if(str_starts_with($image,'/')) return !str_starts_with($image,'//');
        return self::safeTargetUrl($image)!==null;
    }

    private static function locationSpecificity(array $ad): int
    {
        $score=0;
        if(trim((string)($ad['province']??''))!=='') $score++;
        if(trim((string)($ad['district']??''))!=='') $score++;
        return $score;
    }

    private static function fold(string $value): string
    {
        $map=['İ'=>'i','I'=>'i','ı'=>'i','Ş'=>'s','ş'=>'s','Ğ'=>'g','ğ'=>'g','Ü'=>'u','ü'=>'u','Ö'=>'o','ö'=>'o','Ç'=>'c','ç'=>'c'];
        return strtolower(strtr(trim($value),$map));
    }
}
