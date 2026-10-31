<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Ad;

final class AdController
{
    private function gate(): void { AdminAuth::requirePermission('ads.manage'); }

    public function index(): void
    {
        $this->gate();
        View::render('admin/ads/index',['title'=>'Reklam Merkezi','ads'=>Ad::allForAdmin(),'deliveryContext'=>$this->deliveryContext()]);
    }

    public function createForm(): void
    {
        $this->gate();
        View::render('admin/ads/form',['title'=>'Yeni Reklam','ad'=>self::defaults(),'errors'=>[]]);
    }

    public function store(): void
    {
        $this->gate();
        if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $data=self::input(); $errors=Ad::validateInput($data);
        if($errors!==[]){http_response_code(422);View::render('admin/ads/form',['title'=>'Yeni Reklam','ad'=>$data,'errors'=>$errors]);return;}
        $id=Ad::create($data);
        $_SESSION['flash_success']='Reklam oluşturuldu.';
        header('Location: /admin/reklamlar/'.$id.'/duzenle');
    }

    public function editForm(array $p): void
    {
        $this->gate(); $ad=Ad::find((int)($p['id']??0));
        if(!$ad){http_response_code(404);View::render('errors/404',['title'=>'Reklam Bulunamadı']);return;}
        View::render('admin/ads/form',['title'=>'Reklamı Düzenle','ad'=>$ad,'errors'=>[]]);
    }

    public function update(array $p): void
    {
        $this->gate();
        if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        $id=(int)($p['id']??0); if(!Ad::find($id)){http_response_code(404);return;}
        $data=self::input();$errors=Ad::validateInput($data);
        if($errors!==[]){$data['id']=$id;http_response_code(422);View::render('admin/ads/form',['title'=>'Reklamı Düzenle','ad'=>$data,'errors'=>$errors]);return;}
        Ad::update($id,$data);$_SESSION['flash_success']='Reklam güncellendi.';header('Location: /admin/reklamlar/'.$id.'/duzenle');
    }

    public function status(array $p): void
    {
        $this->gate();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        Ad::setStatus((int)($p['id']??0),(string)($_POST['status']??''));
        header('Location: /admin/reklamlar');
    }

    public function delete(array $p): void
    {
        $this->gate();if(!Csrf::validate($_POST['_csrf']??null)){\App\Core\HttpError::render(419);return;}
        Ad::delete((int)($p['id']??0));$_SESSION['flash_success']='Reklam silindi.';header('Location: /admin/reklamlar');
    }

    public function preview(array $p): void
    {
        $this->gate();
        $ad=Ad::find((int)($p['id']??0));
        if(!$ad){http_response_code(404);View::render('errors/404',['title'=>'Reklam Bulunamadı']);return;}
        $baseContext=$this->deliveryContext();
        $context=[
            'placement'=>(string)($_GET['placement']??$ad['placement']),
            'device'=>(string)($_GET['device']??$baseContext['device']),
            'province'=>(string)($_GET['province']??$baseContext['province']),
            'district'=>(string)($_GET['district']??$baseContext['district']),
        ];
        View::render('admin/ads/preview',['title'=>'Reklam Önizleme','ad'=>$ad,'diagnosis'=>Ad::diagnose($ad,$context),'context'=>$context]);
    }

    public function click(array $p): void
    {
        $ad=Ad::find((int)($p['id']??0));
        if(!$ad || (string)($ad['type']??'')!=='manual' || !Ad::isLive($ad)){http_response_code(404);View::render('errors/404',['title'=>'Reklam Bulunamadı']);return;}
        $url=Ad::safeTargetUrl((string)($ad['target_url']??''));
        if($url===null){http_response_code(404);View::render('errors/404',['title'=>'Reklam Bağlantısı Bulunamadı']);return;}
        Ad::recordClick((int)$ad['id']);
        header('Cache-Control: no-store, max-age=0');
        header('Location: '.$url, true, 302);
    }

    private function deliveryContext(): array
    {
        $ua=strtolower((string)($_SERVER['HTTP_USER_AGENT']??''));
        $detected=preg_match('/android|iphone|ipad|ipod|mobile|opera mini|iemobile/',$ua)===1?'mobile':'desktop';
        $requested=(string)($_GET['device']??$detected);
        $device=in_array($requested,['desktop','mobile'],true)?$requested:$detected;
        return [
            'device'=>$device,
            'province'=>trim((string)($_GET['province']??'')),
            'district'=>trim((string)($_GET['district']??'')),
        ];
    }

    private static function input(): array
    {
        return [
            'type'=>(string)($_POST['type']??'manual'),
            'advertiser_name'=>(string)($_POST['advertiser_name']??''),
            'title'=>(string)($_POST['title']??''),
            'body'=>(string)($_POST['body']??''),
            'image_url'=>(string)($_POST['image_url']??''),
            'target_url'=>(string)($_POST['target_url']??''),
            'external_code'=>(string)($_POST['external_code']??''),
            'placement'=>(string)($_POST['placement']??'home_feed'),
            'device_target'=>(string)($_POST['device_target']??'all'),
            'province'=>(string)($_POST['province']??''),
            'district'=>(string)($_POST['district']??''),
            'starts_at'=>(string)($_POST['starts_at']??''),
            'ends_at'=>(string)($_POST['ends_at']??''),
            'status'=>(string)($_POST['status']??'draft'),
        ];
    }

    private static function defaults(): array
    {
        return ['type'=>'manual','advertiser_name'=>'','title'=>'','body'=>'','image_url'=>'','target_url'=>'','external_code'=>'','placement'=>'home_feed','device_target'=>'all','province'=>'','district'=>'','starts_at'=>date('Y-m-d H:i:s'),'ends_at'=>date('Y-m-d H:i:s',strtotime('+30 days')),'status'=>'draft'];
    }
}
