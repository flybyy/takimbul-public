<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;use PDO;
final class SiteSetting
{
    public const KEYS=['announcement_enabled','announcement_text','support_email','home_feature_availability','home_feature_rival_match','require_email_verification','maintenance_message','welcome_ad_enabled'];
    public static function validate(string $key,string $value): ?string
    {
        if(!in_array($key,self::KEYS,true))return'Bilinmeyen ayar.';
        if(in_array($key,['announcement_enabled','home_feature_availability','home_feature_rival_match','require_email_verification','welcome_ad_enabled'],true)&&!in_array($value,['0','1'],true))return'Aç/kapat ayarı 0 veya 1 olmalı.';
        if($key==='support_email'&&!filter_var($value,FILTER_VALIDATE_EMAIL))return'Geçerli destek e-postası gir.';
        if($key==='announcement_text'&&strlen($value)>180)return'Duyuru 180 karakteri geçemez.';
        if($key==='maintenance_message'&&strlen($value)>500)return'Bakım mesajı 500 karakteri geçemez.';
        return null;
    }
    public static function all(): array
    { try{$rows=Database::connection()->query('SELECT setting_key,setting_value FROM site_settings')->fetchAll(PDO::FETCH_KEY_PAIR)?:[];}catch(\Throwable){$rows=[];}return array_merge(['announcement_enabled'=>'0','announcement_text'=>'','support_email'=>'destek@takimbul.com.tr','home_feature_availability'=>'1','home_feature_rival_match'=>'1','require_email_verification'=>'0','maintenance_message'=>'','welcome_ad_enabled'=>'1'],$rows); }
    public static function setMany(array $values,int $adminId): array
    {
        $errors=[];foreach(self::KEYS as $key){$value=(string)($values[$key]??'');if(($e=self::validate($key,$value))!==null)$errors[$key]=$e;}if($errors!==[])return$errors;
        $stmt=Database::connection()->prepare('INSERT INTO site_settings (setting_key,setting_value,updated_by_admin_id) VALUES (:k,:v,:u) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_by_admin_id=VALUES(updated_by_admin_id),updated_at=NOW()');foreach(self::KEYS as $key)$stmt->execute(['k'=>$key,'v'=>(string)$values[$key],'u'=>$adminId]);return[];
    }
    public static function get(string $key,string $default=''): string { $all=self::all();return(string)($all[$key]??$default); }
}
