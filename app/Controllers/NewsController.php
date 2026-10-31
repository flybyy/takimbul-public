<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\HttpError;
use App\Core\View;
use App\Models\AdminAuditLog;
use App\Models\News;
use App\Models\NewsSponsor;
use Throwable;

final class NewsController
{
    public function index(): void { try{$items=News::published(24);}catch(Throwable){$items=[];}View::render('news/index',['title'=>'Haberler','newsItems'=>$items]); }
    public function show(array $params): void { $slug=trim((string)($params['slug']??''));try{$news=News::findPublishedBySlug($slug);}catch(Throwable){$news=null;}if(!$news){HttpError::render(404);return;}try{News::incrementView((int)$news['id']);$news['view_count']=(int)($news['view_count']??0)+1;}catch(Throwable){}View::render('news/show',['title'=>(string)$news['title'],'news'=>$news]); }

    public function adminIndex(): void
    {
        AdminAuth::requirePermission(AdminAuth::can('news.edit_any')?'dashboard':'dashboard.editorial');
        $ownOnly=!AdminAuth::can('news.edit_any');
        View::render('admin/news',['title'=>'Haber Yönetimi','newsItems'=>News::allForAdmin((int)AdminAuth::id(),$ownOnly)]);
    }
    public function adminCreate(): void { AdminAuth::requirePermission('news.create');View::render('admin/news-form',['title'=>'Yeni Haber','news'=>self::defaults(),'errors'=>[],'sponsors'=>self::activeSponsors()]); }
    public function adminStore(): void
    {
        AdminAuth::requirePermission('news.create');if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$data=self::input();$errors=News::validateInput($data);$errors+=self::sponsorErrors($data,0);
        if($errors!==[]){http_response_code(422);View::render('admin/news-form',['title'=>'Yeni Haber','news'=>$data,'errors'=>$errors,'sponsors'=>self::activeSponsors()]);return;}
        $id=News::create($data,(int)AdminAuth::id());AdminAuditLog::record((int)AdminAuth::id(),'news.created','news',$id,['status'=>$data['status'],'sponsor_id'=>(int)($data['sponsor_id']??0)]);if((int)($data['sponsor_id']??0)>0)AdminAuditLog::record((int)AdminAuth::id(),'news.sponsor_attached','news',$id,['sponsor_id'=>(int)$data['sponsor_id']]);$_SESSION['flash_success']='Haber kaydedildi.';header('Location: /admin/haberler/'.$id.'/duzenle');
    }
    public function adminEdit(array $params): void
    {
        AdminAuth::requireLogin();$news=News::find((int)($params['id']??0));if(!$news){HttpError::render(404);return;}if(!self::canMutate($news)){HttpError::render(403);return;}View::render('admin/news-form',['title'=>'Haberi Düzenle','news'=>$news,'errors'=>[],'sponsors'=>self::activeSponsors((int)($news['sponsor_id']??0))]);
    }
    public function adminUpdate(array $params): void
    {
        AdminAuth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$id=(int)($params['id']??0);$current=News::find($id);if(!$current){HttpError::render(404);return;}if(!self::canMutate($current)){HttpError::render(403);return;}$data=self::input();$errors=News::validateInput($data);$errors+=self::sponsorErrors($data,(int)($current['sponsor_id']??0));
        if($errors!==[]){$data['id']=$id;http_response_code(422);View::render('admin/news-form',['title'=>'Haberi Düzenle','news'=>$data,'errors'=>$errors,'sponsors'=>self::activeSponsors((int)($data['sponsor_id']??0))]);return;}
        News::update($id,$data,(int)AdminAuth::id());AdminAuditLog::record((int)AdminAuth::id(),'news.updated','news',$id,['status'=>$data['status'],'sponsor_id'=>(int)($data['sponsor_id']??0)]);if((int)($current['sponsor_id']??0)!==(int)($data['sponsor_id']??0))AdminAuditLog::record((int)AdminAuth::id(),'news.sponsor_attached','news',$id,['previous_sponsor_id'=>(int)($current['sponsor_id']??0),'sponsor_id'=>(int)($data['sponsor_id']??0)]);$_SESSION['flash_success']='Haber güncellendi.';header('Location: /admin/haberler/'.$id.'/duzenle');
    }
    public function adminStatus(array $params): void
    {
        AdminAuth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$id=(int)($params['id']??0);$news=News::find($id);if(!$news){HttpError::render(404);return;}if(!self::canMutate($news)){HttpError::render(403);return;}$status=(string)($_POST['status']??'');News::setStatus($id,$status,(int)AdminAuth::id());AdminAuditLog::record((int)AdminAuth::id(),'news.status_changed','news',$id,['status'=>$status]);header('Location: /admin/haberler');
    }
    public function adminDelete(array $params): void
    {
        AdminAuth::requireLogin();if(!Csrf::validate($_POST['_csrf']??null)){HttpError::render(419);return;}$id=(int)($params['id']??0);$news=News::find($id);if(!$news){HttpError::render(404);return;}if(!self::canMutate($news)){HttpError::render(403);return;}News::delete($id);AdminAuditLog::record((int)AdminAuth::id(),'news.deleted','news',$id,['title'=>(string)$news['title']]);$_SESSION['flash_success']='Haber silindi.';header('Location: /admin/haberler');
    }

    private static function canMutate(array $news): bool { return News::canAdminMutate($news,(int)AdminAuth::id(),(string)AdminAuth::role()) || AdminAuth::can('news.edit_any'); }
    private static function input(): array { return ['title'=>(string)($_POST['title']??''),'summary'=>(string)($_POST['summary']??''),'body'=>(string)($_POST['body']??''),'category'=>(string)($_POST['category']??'TakımBul'),'cover_image_url'=>(string)($_POST['cover_image_url']??''),'seo_title'=>(string)($_POST['seo_title']??''),'seo_description'=>(string)($_POST['seo_description']??''),'status'=>(string)($_POST['status']??'draft'),'is_featured'=>isset($_POST['is_featured'])?'1':'0','sponsor_id'=>(string)($_POST['sponsor_id']??''),'sponsor_cta_text'=>(string)($_POST['sponsor_cta_text']??''),'sponsor_cta_url'=>(string)($_POST['sponsor_cta_url']??'')]; }
    private static function defaults(): array { return ['title'=>'','summary'=>'','body'=>'','category'=>'TakımBul','cover_image_url'=>'','seo_title'=>'','seo_description'=>'','status'=>'draft','is_featured'=>0,'sponsor_id'=>'','sponsor_cta_text'=>'','sponsor_cta_url'=>'']; }
    private static function activeSponsors(int $includeId=0): array { try{return NewsSponsor::activeForSelection($includeId);}catch(Throwable){return [];} }
    private static function sponsorErrors(array $data,int $existingSponsorId=0): array { $id=(int)($data['sponsor_id']??0);if($id<=0)return [];try{if(!NewsSponsor::isSelectable($id,$existingSponsorId))return ['sponsor_id'=>'Seçilen sponsor aktif değil.'];}catch(Throwable){return ['sponsor_id'=>'Sponsor doğrulanamadı.'];}return []; }
}
