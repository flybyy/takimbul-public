<?php

declare(strict_types=1);

namespace App\Core;

final class Seo
{
    private const PRIVATE_PREFIXES = [
        '/giris','/uye-ol','/profil/','/mesaj','/bildirim','/transfer','/basvurular',
        '/mac','/admin','/sikayet','/sifre','/e-posta','/takim-olustur','/scout/takip','/musaitim','/kariyer/vitrin',
        '/hesabim','/baslangic','/takim-bul','/takim-ariyorum','/ara','/amator-ilanim','/hakem-profilim','/teknik-direktor-profilim'
    ];
    private const PRIVATE_CONTAINS = ['/yonet','/kadro','/sonuc','/performans','/teklif','/basvur'];

    public static function canonical(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        if ($path === '//') $path = '/';
        return AppConfig::appUrl() . ($path === '/' ? '/' : $path);
    }

    public static function robotsFor(string $path, array $query): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';
        if ($path === '/rakip-bul/anlik') return 'noindex,nofollow';
        foreach (self::PRIVATE_PREFIXES as $prefix) {
            if ($path === rtrim($prefix, '/') || str_starts_with($path, $prefix)) return 'noindex,nofollow';
        }
        foreach (self::PRIVATE_CONTAINS as $needle) {
            if (str_contains($path, $needle)) return 'noindex,nofollow';
        }
        $meaningful=array_filter(array_keys($query), static fn($key) => !str_starts_with((string)$key,'utm_') && !in_array((string)$key,['gclid','fbclid'],true));
        if ($meaningful !== []) return 'noindex,follow';
        return 'index,follow';
    }

    public static function forRequest(string $path, array $query = [], array $context = []): array
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';
        $title = (string) ($context['title'] ?? 'TakımBul');
        $description = 'Türkiye genelinde halı saha takımı kur, eksik oyuncu ve rakip bul. Maçlarını doğrula, futbol kariyerini oluştur ve scoutlara görün.';

        if ($path === '/') {
            $title = 'Halı Saha Takımı, Oyuncu ve Rakip Bul | TakımBul';
        } elseif ($path === '/takimlar') {
            $title = 'Halı Saha ve Amatör Futbol Takımları | TakımBul';
            $description = 'Türkiye genelindeki halı saha ve amatör futbol takımlarını keşfet, kadrolarını incele ve maç bağlantıları kur.';
        } elseif ($path === '/oyuncu-bul') {
            $title = 'Eksik Oyuncu Bul – Halı Saha Oyuncu İlanları | TakımBul';
            $description = 'Halı saha maçın için kaleci, defans, orta saha, kanat veya forvet bul. Türkiye genelindeki güncel oyuncu ilanlarını keşfet.';
        } elseif ($path === '/rakip-bul') {
            $title = 'Halı Saha Rakip Bul – Türkiye Geneli | TakımBul';
            $description = 'Takımına uygun halı saha rakibi bul, maç teklifi gönder ve maçını TakımBul üzerinden planla.';
        } elseif ($path === '/musait-oyuncular') {
            $title = 'Müsait Halı Saha Oyuncuları – Yakınında Oyuncu Bul | TakımBul';
            $description = 'Şu anda veya yakında maç yapmaya müsait kaleci, defans, orta saha, kanat ve forvetleri il ve ilçeye göre keşfet.';
        } elseif ($path === '/amator-futbolcular') {
            $title = 'Amatör Futbolcular Takım Arıyor | TakımBul';
            $description = 'Takım arayan gerçek amatör futbolcuları il, ilçe, mevki, lisans durumu ve hedef lig seviyesine göre keşfet. Kulüp geçmişi ve oyuncu beyanı istatistiklerini incele.';
        } elseif ($path === '/hakemler') {
            $title='Futbol Hakemleri ve Müsait Hakemler | TakımBul';
            $description='Şehir, klasman, deneyim ve müsaitlik durumuna göre futbol hakemlerini keşfet. Doğrulanmış hakem profillerini TakımBul üzerinden incele.';
        } elseif ($path === '/teknik-direktorler') {
            $title='Teknik Direktörler ve Futbol Antrenörleri | TakımBul';
            $description='Teknik direktör ve antrenörleri şehir, lisans, uzmanlık ve çalışma durumuna göre keşfet. Teknik kariyer profillerini TakımBul üzerinden incele.';
        } elseif (preg_match('#^/hakem/[a-z0-9-]+$#',$path) && isset($context['refereeProfile'])) {
            $r=$context['refereeProfile'];$title=trim((string)($r['display_name']??'Hakem')).' - Hakem, '.trim((string)($r['district']??$r['province']??'' )).' | TakımBul';$description=trim((string)($r['display_name']??'Hakem')).' futbol hakemi profili, klasman, deneyim, lisans ve müsaitlik bilgileri TakımBul’da.';
        } elseif (preg_match('#^/teknik-direktor/[a-z0-9-]+$#',$path) && isset($context['coachProfile'])) {
            $c=$context['coachProfile'];$title=trim((string)($c['display_name']??'Teknik Direktör')).' - '.trim((string)($c['public_title']??'Teknik Direktör')).', '.trim((string)($c['district']??$c['province']??'' )).' | TakımBul';$description=trim((string)($c['display_name']??'Teknik Direktör')).' teknik direktör/antrenör profili, lisans, uzmanlık, kulüp geçmişi ve çalışma durumu TakımBul’da.';
        } elseif (preg_match('#^/yazar/[a-z0-9-]+$#',$path) && isset($context['author'])) {
            $author=$context['author'];$person=['@type'=>'Person','name'=>(string)($author['display_name']??''),'url'=>self::canonical($path)];if(!empty($author['public_title']))$person['jobTitle']=(string)$author['public_title'];if(!empty($author['public_bio']))$person['description']=(string)$author['public_bio'];$jsonLd[]=['@context'=>'https://schema.org','@type'=>'ProfilePage','mainEntity'=>$person,'url'=>self::canonical($path),'inLanguage'=>'tr-TR'];$jsonLd[]=self::breadcrumb([['TakımBul','/'],['Haberler','/haberler'],[(string)($author['display_name']??'Yazar'),$path]]);
        } elseif (preg_match('#^/amator-futbolcu/[a-z0-9-]+$#', $path) && isset($context['amateurAd'])) {
            $ad=$context['amateurAd'];$name=trim((string)($ad['display_name']??'Futbolcu'));$position=trim((string)($ad['primary_position']??''));$location=trim((string)(($ad['district']??'').', '.($ad['province']??'')),', ');
            $title=$name.' Takım Arıyor'.($position!==''?' – '.$position:'').' | TakımBul';
            $description=$name.($position!==''?' · '.$position:'').($location!==''?' · '.$location:'').' amatör futbolcu takım arama ilanı, geçmiş kulüpleri ve kariyer özeti TakımBul’da.';
        } elseif ($path === '/siralama') {
            $title = 'Amatör Futbol Oyuncu Sıralaması ve İstatistikleri | TakımBul';
            $description = 'Doğrulanmış maçlara göre sezon puanı, gol, asist, MVP ve maç sayısı sıralamalarını incele.';
        } elseif ($path === '/scout') {
            $title = 'Amatör Futbolcu Keşfi – Kulüp ve Scout Ağı | TakımBul';
            $description = 'Doğrulanmış kulüp ve scout hesapları için amatör futbolcu keşif ağı. Oyuncu kariyerlerini ve doğrulanmış performansı incele.';
        } elseif ($path === '/haberler') {
            $title = 'Amatör Futbol ve TakımBul Haberleri | TakımBul';
            $description = 'TakımBul gelişmeleri, amatör futbol, transfer, halı saha ve topluluk duyurularını takip et.';
        } elseif (preg_match('#^/haber/[a-z0-9-]+$#', $path) && isset($context['news'])) {
            $news=$context['news'];
            $title=trim((string)($news['seo_title']??'')) ?: ((string)($news['title']??'Haber').' | TakımBul');
            $description=trim((string)($news['seo_description']??'')) ?: (trim((string)($news['summary']??'')) ?: 'TakımBul amatör futbol ve topluluk haberi.');
        } elseif (preg_match('#^/yazar/[a-z0-9-]+$#',$path) && isset($context['author'])) {
            $author=$context['author'];$name=trim((string)($author['display_name']??'TakımBul Yazarı'));$title=$name.' Yazıları | TakımBul';$bio=trim((string)($author['public_bio']??''));$publicTitle=trim((string)($author['public_title']??'TakımBul Ekibi'));$description=$bio!==''?$bio:($name.' · '.$publicTitle.' tarafından yayınlanan TakımBul haberleri ve yazıları.');
        } elseif ($path === '/rehber' || str_starts_with($path, '/rehber/')) {
            $title = (string) ($context['seoTitle'] ?? ($title . ' | TakımBul Rehber'));
            $description = (string) ($context['seoDescription'] ?? 'Halı saha, takım kurma, rakip bulma ve amatör futbol kariyeri için TakımBul rehberleri.');
        } elseif (preg_match('#^/takim/\d+$#', $path) && isset($context['team'])) {
            $team = $context['team'];
            $name = trim((string) ($team['name'] ?? 'Takım'));
            $location = trim((string) (($team['district'] ?? '') . ', ' . ($team['province'] ?? '')), ', ');
            $rosterCount=is_array($team['roster']??null)?count($team['roster']):0;
            $title = $name . ' Kadrosu, Oyuncuları ve Maçları | TakımBul';
            $description = $name . ($location !== '' ? ' · ' . $location : '') . ($rosterCount>0?' · '.$rosterCount.' aktif oyuncu':'') . ' kadrosu, futbolcuları, dizilişi, doğrulanmış maçları ve takım istatistikleri TakımBul’da.';
        } elseif (preg_match('#^/hakem/[a-z0-9-]+$#',$path) && isset($context['refereeProfile'])) {
            $r=$context['refereeProfile'];$person=['@type'=>'Person','name'=>(string)($r['display_name']??''),'jobTitle'=>'Football Referee','url'=>self::canonical($path),'address'=>['@type'=>'PostalAddress','addressLocality'=>(string)($r['district']??''),'addressRegion'=>(string)($r['province']??''),'addressCountry'=>'TR']];$jsonLd[]=['@context'=>'https://schema.org','@type'=>'ProfilePage','mainEntity'=>$person,'url'=>self::canonical($path),'inLanguage'=>'tr-TR'];$jsonLd[]=self::breadcrumb([['TakımBul','/'],['Hakemler','/hakemler'],[(string)($r['display_name']??'Hakem'),$path]]);
        } elseif (preg_match('#^/teknik-direktor/[a-z0-9-]+$#',$path) && isset($context['coachProfile'])) {
            $c=$context['coachProfile'];$person=['@type'=>'Person','name'=>(string)($c['display_name']??''),'jobTitle'=>'Football Coach','url'=>self::canonical($path),'address'=>['@type'=>'PostalAddress','addressLocality'=>(string)($c['district']??''),'addressRegion'=>(string)($c['province']??''),'addressCountry'=>'TR']];$jsonLd[]=['@context'=>'https://schema.org','@type'=>'ProfilePage','mainEntity'=>$person,'url'=>self::canonical($path),'inLanguage'=>'tr-TR'];$jsonLd[]=self::breadcrumb([['TakımBul','/'],['Teknik Direktörler','/teknik-direktorler'],[(string)($c['display_name']??'Teknik Direktör'),$path]]);
        } elseif (preg_match('#^/kariyer/\d+$#', $path) && isset($context['player'])) {
            $player = $context['player'];
            $profile = $context['profile'] ?? [];
            $name = trim((string) ($player['display_name'] ?? 'Oyuncu'));
            $position = trim((string) ($profile['primary_position'] ?? ''));
            $title = $name . ' Futbol Kariyeri, Maç ve İstatistikleri | TakımBul';
            $description = $name . ($position !== '' ? ' · ' . $position : '') . ' için doğrulanmış maç, gol, asist, MVP ve sezon performansı TakımBul kariyer profilinde.';
        }

        if (stripos($title,'TakımBul') === false && stripos($title,'takimbul') === false) $title .= ' | TakımBul';

        $jsonLd = [];
        if ($path === '/') {
            $jsonLd[] = [
                '@context'=>'https://schema.org','@type'=>'WebSite','name'=>'TakımBul','alternateName'=>'takimbul',
                'url'=>AppConfig::appUrl() . '/','inLanguage'=>'tr-TR'
            ];
            $jsonLd[] = [
                '@context'=>'https://schema.org','@type'=>'Organization','name'=>'TakımBul','url'=>AppConfig::appUrl() . '/',
                'description'=>$description
            ];
        }
        if (preg_match('#^/haber/[a-z0-9-]+$#', $path) && isset($context['news'])) {
            $news=$context['news'];
            $published=!empty($news['published_at'])?date(DATE_ATOM,strtotime((string)$news['published_at'])):null;
            $author=['@type'=>'Organization','name'=>'TakımBul'];if(!empty($news['author_name'])){$author=['@type'=>'Person','name'=>(string)$news['author_name']];if(!empty($news['author_slug'])&&!empty($news['author_public_profile']))$author['url']=self::canonical('/yazar/'.(string)$news['author_slug']);}$article=['@context'=>'https://schema.org','@type'=>'NewsArticle','headline'=>(string)($news['title']??''),'description'=>$description,'mainEntityOfPage'=>self::canonical($path),'author'=>$author,'publisher'=>['@type'=>'Organization','name'=>'TakımBul'],'inLanguage'=>'tr-TR'];
            if($published)$article['datePublished']=$published;
            if(!empty($news['updated_at']))$article['dateModified']=date(DATE_ATOM,strtotime((string)$news['updated_at']));
            $jsonLd[]=$article;
            $jsonLd[]=self::breadcrumb([['TakımBul','/'],['Haberler','/haberler'],[(string)($news['title']??'Haber'),$path]]);
        } elseif (preg_match('#^/yazar/[a-z0-9-]+$#',$path) && isset($context['author'])) {
            $author=$context['author'];$person=['@type'=>'Person','name'=>(string)($author['display_name']??''),'url'=>self::canonical($path)];if(!empty($author['public_title']))$person['jobTitle']=(string)$author['public_title'];if(!empty($author['public_bio']))$person['description']=(string)$author['public_bio'];$jsonLd[]=['@context'=>'https://schema.org','@type'=>'ProfilePage','mainEntity'=>$person,'url'=>self::canonical($path),'inLanguage'=>'tr-TR'];$jsonLd[]=self::breadcrumb([['TakımBul','/'],['Haberler','/haberler'],[(string)($author['display_name']??'Yazar'),$path]]);
        } elseif (preg_match('#^/amator-futbolcu/[a-z0-9-]+$#', $path) && isset($context['amateurAd'])) {
            $ad=$context['amateurAd'];$person=['@type'=>'Person','name'=>(string)($ad['display_name']??''),'url'=>self::canonical($path),'address'=>['@type'=>'PostalAddress','addressLocality'=>(string)($ad['district']??''),'addressRegion'=>(string)($ad['province']??''),'addressCountry'=>'TR']];
            if(!empty($ad['primary_position']))$person['jobTitle']=(string)$ad['primary_position'].' futbolcu';
            $jsonLd[]=['@context'=>'https://schema.org','@type'=>'ProfilePage','mainEntity'=>$person,'url'=>self::canonical($path),'inLanguage'=>'tr-TR'];
            $jsonLd[]=self::breadcrumb([['TakımBul','/'],['Amatör Futbolcular','/amator-futbolcular'],[(string)($ad['display_name']??'Futbolcu'),$path]]);
        } elseif (preg_match('#^/hakem/[a-z0-9-]+$#',$path) && isset($context['refereeProfile'])) {
            $r=$context['refereeProfile'];$person=['@type'=>'Person','name'=>(string)($r['display_name']??''),'jobTitle'=>'Football Referee','url'=>self::canonical($path),'address'=>['@type'=>'PostalAddress','addressLocality'=>(string)($r['district']??''),'addressRegion'=>(string)($r['province']??''),'addressCountry'=>'TR']];$jsonLd[]=['@context'=>'https://schema.org','@type'=>'ProfilePage','mainEntity'=>$person,'url'=>self::canonical($path),'inLanguage'=>'tr-TR'];$jsonLd[]=self::breadcrumb([['TakımBul','/'],['Hakemler','/hakemler'],[(string)($r['display_name']??'Hakem'),$path]]);
        } elseif (preg_match('#^/teknik-direktor/[a-z0-9-]+$#',$path) && isset($context['coachProfile'])) {
            $c=$context['coachProfile'];$person=['@type'=>'Person','name'=>(string)($c['display_name']??''),'jobTitle'=>'Football Coach','url'=>self::canonical($path),'address'=>['@type'=>'PostalAddress','addressLocality'=>(string)($c['district']??''),'addressRegion'=>(string)($c['province']??''),'addressCountry'=>'TR']];$jsonLd[]=['@context'=>'https://schema.org','@type'=>'ProfilePage','mainEntity'=>$person,'url'=>self::canonical($path),'inLanguage'=>'tr-TR'];$jsonLd[]=self::breadcrumb([['TakımBul','/'],['Teknik Direktörler','/teknik-direktorler'],[(string)($c['display_name']??'Teknik Direktör'),$path]]);
        } elseif (preg_match('#^/kariyer/\d+$#', $path) && isset($context['player'])) {
            $player = $context['player'];
            $profile = $context['profile'] ?? [];
            $person = [
                '@type'=>'Person','name'=>(string)($player['display_name'] ?? ''),
                'url'=>self::canonical($path),'address'=>[
                    '@type'=>'PostalAddress','addressLocality'=>(string)($player['district'] ?? ''),'addressRegion'=>(string)($player['province'] ?? ''),'addressCountry'=>'TR'
                ]
            ];
            if (!empty($profile['primary_position'])) $person['jobTitle'] = (string)$profile['primary_position'] . ' futbolcu';
            $jsonLd[] = ['@context'=>'https://schema.org','@type'=>'ProfilePage','mainEntity'=>$person,'url'=>self::canonical($path),'inLanguage'=>'tr-TR'];
            $jsonLd[] = self::breadcrumb([['TakımBul','/'],['Oyuncu Kariyeri',$path]]);
        } elseif (preg_match('#^/takim/\d+$#', $path) && isset($context['team'])) {
            $team=$context['team'];
            $sportsTeam=['@context'=>'https://schema.org','@type'=>'SportsTeam','name'=>(string)($team['name']??''),'sport'=>'Football','url'=>self::canonical($path),'location'=>['@type'=>'Place','address'=>['@type'=>'PostalAddress','addressLocality'=>(string)($team['district']??''),'addressRegion'=>(string)($team['province']??''),'addressCountry'=>'TR']]];if(!empty($team['founded_year']))$sportsTeam['foundingDate']=(string)$team['founded_year'];if(!empty($team['logo_path']))$sportsTeam['logo']=str_starts_with((string)$team['logo_path'],'http')?(string)$team['logo_path']:AppConfig::appUrl().(string)$team['logo_path'];$jsonLd[]=$sportsTeam;
            $jsonLd[] = self::breadcrumb([['TakımBul','/'],['Takımlar','/takimlar'],[(string)($team['name'] ?? 'Takım'),$path]]);
        } elseif (str_starts_with($path,'/rehber/') && isset($context['seoTitle'])) {
            $jsonLd[]=['@context'=>'https://schema.org','@type'=>'Article','headline'=>(string)($context['title']??$title),'description'=>$description,'mainEntityOfPage'=>self::canonical($path),'author'=>['@type'=>'Organization','name'=>'TakımBul'],'publisher'=>['@type'=>'Organization','name'=>'TakımBul'],'inLanguage'=>'tr-TR'];
            $jsonLd[] = self::breadcrumb([['TakımBul','/'],['Rehber','/rehber'],[(string)($context['title']??'Rehber'),$path]]);
        } elseif ($path !== '/') {
            $label = trim(preg_replace('/\s+/', ' ', strip_tags($title))) ?: 'TakımBul';
            $jsonLd[] = self::breadcrumb([['TakımBul','/'],[$label,$path]]);
        }

        return [
            'title'=>$title,'description'=>$description,'canonical'=>self::canonical($path),'robots'=>self::robotsFor($path,$query),
            'ogType'=>(str_starts_with($path,'/haber/')?'article':(preg_match('#^/(kariyer/\d+|amator-futbolcu/[a-z0-9-]+|hakem/[a-z0-9-]+|teknik-direktor/[a-z0-9-]+)$#',$path)?'profile':'website')),'jsonLd'=>$jsonLd,
        ];
    }

    private static function breadcrumb(array $items): array
    {
        $elements=[];
        foreach ($items as $index=>$item) {
            $elements[]=['@type'=>'ListItem','position'=>$index+1,'name'=>$item[0],'item'=>self::canonical($item[1])];
        }
        return ['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$elements];
    }
}
