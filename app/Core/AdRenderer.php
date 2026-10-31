<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Ad;
use App\Models\SiteSetting;
use Throwable;

final class AdRenderer
{
    public static function slot(string $placement,array $context=[]): string
    {
        $province=trim((string)($context['province']??''));
        $district=trim((string)($context['district']??''));
        $device=(string)($context['device']??self::device());
        if($placement==='mobile_feed' && $device!=='mobile') return '';
        try {
            $ad=Ad::pickForContext($placement,$province,$district,$device);
            if(!$ad) return '';
            Ad::recordImpression((int)$ad['id']);
            return '<div class="ad-slot ad-slot-'.self::escAttr($placement).'" data-ad-placement="'.self::escAttr($placement).'" aria-label="Sponsorlu içerik">'.self::renderAd($ad).'</div>';
        } catch(Throwable) {
            return '';
        }
    }

    public static function welcomeTakeover(array $context=[]): string
    {
        if (SiteSetting::get('welcome_ad_enabled','1') !== '1') return '';
        if (isset($_COOKIE['takimbul_welcome_ad_seen']) && (string)$_COOKIE['takimbul_welcome_ad_seen']==='1') return '';
        $province=trim((string)($context['province']??''));$district=trim((string)($context['district']??''));$device=(string)($context['device']??self::device());
        try {
            $ad=Ad::pickForContext('welcome_takeover',$province,$district,$device);if(!$ad)return'';
            $body=self::renderAd($ad);if($body==='')return'';Ad::recordImpression((int)$ad['id']);
            return '<div class="welcome-ad-shell"><div class="welcome-ad-kicker">TAKIMBUL SPONSORLU</div>'.$body.'</div>';
        } catch(Throwable) { return ''; }
    }

    public static function preview(array $ad): string
    {
        $placement=(string)($ad['placement'] ?? 'preview');
        $body=self::renderAd($ad);
        return $body===''?'':'<div class="ad-slot ad-preview-slot ad-slot-'.self::escAttr($placement).'" data-ad-preview="1" aria-label="Sponsorlu içerik">'.$body.'</div>';
    }

    public static function renderAd(array $ad): string
    {
        $type=(string)($ad['type']??'manual');
        $advertiser=self::esc((string)($ad['advertiser_name']??''));
        $title=self::esc((string)($ad['title']??''));
        if($type==='external_code'){
            $code=(string)($ad['external_code']??'');
            if(trim($code)==='') return '';
            return '<aside class="ad-card ad-card-external"><div class="ad-sponsored-label">Sponsorlu · '.($advertiser!==''?$advertiser:'Harici reklam').'</div><div class="ad-external-frame">'.$code.'</div></aside>';
        }
        $id=(int)($ad['id']??0);
        if($id<1) return '';
        $body=self::esc((string)($ad['body']??''));
        $image=trim((string)($ad['image_url']??''));
        $imageHtml='';
        if($image!=='' && (str_starts_with($image,'/') || Ad::safeTargetUrl($image)!==null)){
            $imageHtml='<div class="ad-card-media"><img src="'.self::escAttr($image).'" alt="'.self::escAttr($title).'" loading="lazy" decoding="async" width="640" height="360"></div>';
        }
        return '<aside class="ad-card ad-card-manual">'.$imageHtml.'<div class="ad-card-content"><div class="ad-sponsored-label">Sponsorlu · '.$advertiser.'</div><strong>'.$title.'</strong>'.($body!==''?'<p>'.$body.'</p>':'').'<a class="ad-card-cta" href="/reklam/tikla/'.$id.'" rel="sponsored noopener">İncele <span aria-hidden="true">→</span></a></div></aside>';
    }

    private static function device(): string
    {
        $ua=strtolower((string)($_SERVER['HTTP_USER_AGENT']??''));
        return preg_match('/android|iphone|ipad|ipod|mobile|opera mini|iemobile/',$ua)===1?'mobile':'desktop';
    }

    private static function esc(string $value): string{return htmlspecialchars($value,ENT_QUOTES,'UTF-8');}
    private static function escAttr(string $value): string{return htmlspecialchars($value,ENT_QUOTES,'UTF-8');}
}
