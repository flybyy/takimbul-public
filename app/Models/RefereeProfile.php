<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\TurkeyLocation;
use PDO;
use RuntimeException;

final class RefereeProfile
{
    public const AVAILABILITY=['showcase','available','unavailable'];
    public const LICENSE_STATUSES=['unverified','verified','rejected'];

    public static function validateInput(array $data): array
    {
        $errors=[];$province=trim((string)($data['province']??''));$district=trim((string)($data['district']??''));
        $classification=trim((string)($data['classification']??''));$started=trim((string)($data['started_year']??''));
        $availability=trim((string)($data['availability_status']??'showcase'));$bio=trim((string)($data['bio']??''));
        if($province===''||TurkeyLocation::canonicalProvince($province)===null)$errors['province']='Geçerli bir il seç.';
        elseif($district===''||!TurkeyLocation::isValidPair($province,$district))$errors['district']='Seçtiğin ilçe bu ile ait değil.';
        if(self::length($classification)>100)$errors['classification']='Klasman en fazla 100 karakter olabilir.';
        $year=(int)date('Y');if($started!==''&&(!ctype_digit($started)||(int)$started<1950||(int)$started>$year))$errors['started_year']='Hakemliğe başlangıç yılı geçersiz.';
        if(!in_array($availability,self::AVAILABILITY,true))$errors['availability_status']='Müsaitlik durumu geçersiz.';
        if(self::length(trim((string)($data['license_name']??'')))>160)$errors['license_name']='Lisans/sertifika adı çok uzun.';
        if(self::length(trim((string)($data['competitions_text']??'')))>700)$errors['competitions_text']='Lig/turnuva bilgisi en fazla 700 karakter olabilir.';
        if(self::length($bio)>1200)$errors['bio']='Biyografi en fazla 1200 karakter olabilir.';
        $show=!empty($data['fee_visibility']);$min=trim((string)($data['fee_min']??''));$max=trim((string)($data['fee_max']??''));
        foreach(['fee_min'=>$min,'fee_max'=>$max] as $k=>$v)if($v!==''&&(!is_numeric($v)||(float)$v<0||(float)$v>1000000))$errors[$k]='Ücret alanı geçersiz.';
        if($show&&$min!==''&&$max!==''&&is_numeric($min)&&is_numeric($max)&&(float)$max<(float)$min)$errors['fee_max']='Maksimum ücret minimum ücretten düşük olamaz.';
        return $errors;
    }

    public static function makeSlug(string $name,int $userId): string
    {
        $map=['İ'=>'i','I'=>'i','ı'=>'i','Ş'=>'s','ş'=>'s','Ğ'=>'g','ğ'=>'g','Ü'=>'u','ü'=>'u','Ö'=>'o','ö'=>'o','Ç'=>'c','ç'=>'c'];
        $slug=strtolower(strtr(trim($name),$map));$slug=preg_replace('/[^a-z0-9]+/','-',$slug)??'';$slug=trim($slug,'-');if($slug==='')$slug='hakem';return substr($slug,0,135).'-'.$userId;
    }

    public static function save(int $userId,array $data,bool $submit): int
    {
        $errors=self::validateInput($data);if($errors!==[])throw new RuntimeException((string)reset($errors));
        $identity=User::profileIdentity($userId);if(!$identity||($identity['status']??'')!=='active')throw new RuntimeException('Aktif kullanıcı hesabı bulunamadı.');
        FootballIdentity::syncForUser($userId,['referee'],'referee');
        $existing=self::forOwner($userId);$slug=$existing['slug']??self::makeSlug((string)$identity['display_name'],$userId);$status=$submit?'pending':'draft';
        $params=[
            'user_id'=>$userId,'slug'=>$slug,
            'province'=>TurkeyLocation::canonicalProvince((string)$data['province'])??trim((string)$data['province']),
            'district'=>TurkeyLocation::canonicalDistrict((string)$data['province'],(string)$data['district'])??trim((string)$data['district']),
            'classification'=>self::nullEmpty($data['classification']??null),'started_year'=>self::intOrNull($data['started_year']??null),
            'license_name'=>self::nullEmpty($data['license_name']??null),'availability_status'=>(string)($data['availability_status']??'showcase'),
            'fee_visibility'=>!empty($data['fee_visibility'])?1:0,'fee_min'=>self::decimalOrNull($data['fee_min']??null),'fee_max'=>self::decimalOrNull($data['fee_max']??null),
            'competitions_text'=>self::nullEmpty($data['competitions_text']??null),'bio'=>self::nullEmpty($data['bio']??null),'status'=>$status,
        ];
        $pdo=Database::connection();
        if($existing){
            $params['id']=(int)$existing['id'];
            $sql='UPDATE referee_profiles SET slug=:slug,province=:province,district=:district,classification=:classification,started_year=:started_year,license_name=:license_name,availability_status=:availability_status,fee_visibility=:fee_visibility,fee_min=:fee_min,fee_max=:fee_max,competitions_text=:competitions_text,bio=:bio,status=:status,moderation_note=NULL,updated_at=NOW() WHERE id=:id AND user_id=:user_id';
        }else{
            $sql='INSERT INTO referee_profiles (user_id,slug,province,district,classification,started_year,license_name,availability_status,fee_visibility,fee_min,fee_max,competitions_text,bio,status) VALUES (:user_id,:slug,:province,:district,:classification,:started_year,:license_name,:availability_status,:fee_visibility,:fee_min,:fee_max,:competitions_text,:bio,:status)';
        }
        $stmt=$pdo->prepare($sql);$stmt->execute($params);$id=$existing?(int)$existing['id']:(int)$pdo->lastInsertId();
        FootballIdentity::setStatus($userId,'referee',$submit?'pending':'draft',false);return $id;
    }

    public static function forOwner(int $userId): ?array
    {
        $stmt=Database::connection()->prepare('SELECT r.*,u.display_name,u.username,u.profile_verified_at,p.avatar_path FROM referee_profiles r INNER JOIN users u ON u.id=r.user_id LEFT JOIN player_profiles p ON p.user_id=u.id WHERE r.user_id=:user_id LIMIT 1');$stmt->execute(['user_id'=>$userId]);$row=$stmt->fetch(PDO::FETCH_ASSOC);return is_array($row)?$row:null;
    }

    public static function findPublicBySlug(string $slug): ?array
    {
        $stmt=Database::connection()->prepare('SELECT r.*,u.display_name,u.username,u.profile_verified_at,u.created_at,p.avatar_path FROM referee_profiles r INNER JOIN users u ON u.id=r.user_id LEFT JOIN player_profiles p ON p.user_id=u.id WHERE r.slug=:slug AND r.status="active" AND u.status="active" LIMIT 1');$stmt->execute(['slug'=>$slug]);$row=$stmt->fetch(PDO::FETCH_ASSOC);return is_array($row)?$row:null;
    }

    public static function search(array $filters=[],int $limit=48): array
    {
        $limit=max(1,min(100,$limit));$sql='SELECT r.*,u.display_name,u.username,u.profile_verified_at,p.avatar_path FROM referee_profiles r INNER JOIN users u ON u.id=r.user_id LEFT JOIN player_profiles p ON p.user_id=u.id WHERE r.status="active" AND u.status="active"';$params=[];
        foreach(['province','district','availability_status'] as $key){$v=trim((string)($filters[$key]??''));if($v!==''){$sql.=' AND r.'.$key.'=:'.$key;$params[$key]=$v;}}
        $classification=trim((string)($filters['classification']??''));if($classification!==''){$sql.=' AND r.classification LIKE :classification';$params['classification']='%'.$classification.'%';}
        if(!empty($filters['verified']))$sql.=' AND r.license_status="verified"';
        $experience=max(0,(int)($filters['experience_years']??0));if($experience>0){$sql.=' AND r.started_year IS NOT NULL AND r.started_year<=:max_start';$params['max_start']=(int)date('Y')-$experience;}
        $sql.=' ORDER BY (r.license_status="verified") DESC,(r.availability_status="available") DESC,r.updated_at DESC LIMIT '.$limit;
        $stmt=Database::connection()->prepare($sql);$stmt->execute($params);return $stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    public static function adminFind(int $id): ?array{$s=Database::connection()->prepare('SELECT r.*,u.display_name,u.username,u.email,u.phone FROM referee_profiles r INNER JOIN users u ON u.id=r.user_id WHERE r.id=:id LIMIT 1');$s->execute(['id'=>$id]);$r=$s->fetch(PDO::FETCH_ASSOC);return is_array($r)?$r:null;}
    public static function adminList(int $limit=100): array{$limit=max(1,min(200,$limit));return Database::connection()->query('SELECT r.*,u.display_name,u.username FROM referee_profiles r INNER JOIN users u ON u.id=r.user_id ORDER BY r.updated_at DESC LIMIT '.$limit)->fetchAll(PDO::FETCH_ASSOC)?:[];}
    public static function setAdminStatus(int $id,string $status,?string $note=null): bool{if(!in_array($status,['active','suspended','rejected','pending'],true))return false;$s=Database::connection()->prepare('UPDATE referee_profiles SET status=:status,moderation_note=:note,published_at=CASE WHEN :status2="active" THEN COALESCE(published_at,NOW()) ELSE published_at END,updated_at=NOW() WHERE id=:id');$s->execute(['status'=>$status,'note'=>self::nullEmpty($note),'status2'=>$status,'id'=>$id]);if($s->rowCount()>=0){$p=self::adminFind($id);if($p)FootballIdentity::setStatus((int)$p['user_id'],'referee',$status==='active'?'active':($status==='suspended'?'suspended':'pending'),$status==='active');return true;}return false;}
    public static function setLicenseStatus(int $id,string $status,int $adminId): bool{if(!in_array($status,self::LICENSE_STATUSES,true)||$adminId<=0)return false;$s=Database::connection()->prepare('UPDATE referee_profiles SET license_status=:status,license_verified_by_admin_id=CASE WHEN :status2="verified" THEN :admin ELSE NULL END,license_verified_at=CASE WHEN :status3="verified" THEN NOW() ELSE NULL END,updated_at=NOW() WHERE id=:id');return $s->execute(['status'=>$status,'status2'=>$status,'status3'=>$status,'admin'=>$adminId,'id'=>$id]);}

    private static function nullEmpty(mixed $v): ?string{$v=trim((string)$v);return $v===''?null:$v;}
    private static function intOrNull(mixed $v): ?int{$v=trim((string)$v);return $v===''?null:(int)$v;}
    private static function decimalOrNull(mixed $v): ?string{$v=trim((string)$v);return $v===''?null:number_format((float)$v,2,'.','');}
    private static function length(string $v): int{return function_exists('mb_strlen')?mb_strlen($v,'UTF-8'):strlen($v);}
}
