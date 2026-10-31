<?php
declare(strict_types=1);
namespace App\Core;

final class TurkeyLocation
{
    private static ?array $data=null;

    public static function all(): array
    {
        if(self::$data!==null) return self::$data;
        $path=dirname(__DIR__,2).'/public/assets/data/turkey-locations.json';
        $decoded=is_file($path)?json_decode((string)file_get_contents($path),true):null;
        self::$data=is_array($decoded)?$decoded:[];
        return self::$data;
    }

    public static function provinces(): array { return array_keys(self::all()); }
    public static function districts(string $province): array
    {
        $canonical=self::canonicalProvince($province);
        return $canonical!==null?(self::all()[$canonical]??[]):[];
    }
    public static function districtCount(): int
    {
        return array_sum(array_map('count',self::all()));
    }
    public static function provinceOptions(string $selected='',string $emptyLabel='İl seç'): string
    {
        $canonical=self::canonicalProvince($selected) ?? trim($selected);
        $html='<option value="">'.htmlspecialchars($emptyLabel,ENT_QUOTES,'UTF-8').'</option>';
        foreach(self::provinces() as $province){
            $html.='<option value="'.htmlspecialchars($province,ENT_QUOTES,'UTF-8').'"'.($province===$canonical?' selected':'').'>'.htmlspecialchars($province,ENT_QUOTES,'UTF-8').'</option>';
        }
        return $html;
    }
    public static function districtOptions(string $province,string $selected='',string $emptyLabel='İlçe seç'): string
    {
        $canonical=self::canonicalDistrict($province,$selected) ?? trim($selected);
        $html='<option value="">'.htmlspecialchars($emptyLabel,ENT_QUOTES,'UTF-8').'</option>';
        foreach(self::districts($province) as $district){
            $html.='<option value="'.htmlspecialchars($district,ENT_QUOTES,'UTF-8').'"'.($district===$canonical?' selected':'').'>'.htmlspecialchars($district,ENT_QUOTES,'UTF-8').'</option>';
        }
        return $html;
    }
    public static function canonicalProvince(string $province): ?string
    {
        $needle=self::fold($province);
        foreach(self::provinces() as $candidate) if(self::fold($candidate)===$needle) return $candidate;
        return null;
    }
    public static function canonicalDistrict(string $province,string $district): ?string
    {
        $needle=self::fold($district);
        foreach(self::districts($province) as $candidate) if(self::fold($candidate)===$needle) return $candidate;
        return null;
    }
    public static function isValidPair(string $province,string $district): bool
    {
        return self::canonicalProvince($province)!==null && self::canonicalDistrict($province,$district)!==null;
    }
    private static function fold(string $value): string
    {
        $value=trim($value);
        if(function_exists('mb_strtolower')) return mb_strtolower($value,'UTF-8');
        return strtolower(strtr($value,['İ'=>'i','I'=>'ı']));
    }
}
