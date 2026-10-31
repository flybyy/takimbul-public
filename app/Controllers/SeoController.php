<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppConfig;
use App\Core\Database;
use PDO;
use Throwable;

final class SeoController
{
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        $base=AppConfig::appUrl();
        echo "User-agent: *\n";
        echo "Allow: /\n";
        foreach(['/admin','/admin/','/baslangic','/mesajlar','/bildirimler','/transferler','/profil/','/basvurular','/mac','/scout/takip','/sikayet','/sifre-','/e-posta-','/musaitim','/rakip-bul/anlik','/kariyer/vitrin'] as $path) echo "Disallow: {$path}\n";
        echo "Sitemap: {$base}/sitemap.xml\n";
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        $urls=[
            ['loc'=>'/','priority'=>'1.0','freq'=>'daily'],['loc'=>'/takimlar','priority'=>'0.8','freq'=>'daily'],
            ['loc'=>'/oyuncu-bul','priority'=>'0.9','freq'=>'hourly'],['loc'=>'/amator-futbolcular','priority'=>'0.9','freq'=>'hourly'],['loc'=>'/rakip-bul','priority'=>'0.9','freq'=>'hourly'],['loc'=>'/musait-oyuncular','priority'=>'0.8','freq'=>'hourly'],
            ['loc'=>'/siralama','priority'=>'0.8','freq'=>'daily'],['loc'=>'/scout','priority'=>'0.6','freq'=>'weekly'],
            ['loc'=>'/haberler','priority'=>'0.8','freq'=>'daily'],['loc'=>'/hakemler','priority'=>'0.8','freq'=>'daily'],['loc'=>'/teknik-direktorler','priority'=>'0.8','freq'=>'daily'],
            ['loc'=>'/rehber','priority'=>'0.7','freq'=>'weekly'],
            ['loc'=>'/rehber/hali-saha-takimi-nasil-kurulur','priority'=>'0.7','freq'=>'monthly'],
            ['loc'=>'/rehber/eksik-oyuncu-nasil-bulunur','priority'=>'0.7','freq'=>'monthly'],
            ['loc'=>'/rehber/hali-saha-rakip-nasil-bulunur','priority'=>'0.7','freq'=>'monthly'],
            ['loc'=>'/hakkimizda','priority'=>'0.5','freq'=>'yearly'],['loc'=>'/iletisim','priority'=>'0.4','freq'=>'yearly'],
            ['loc'=>'/gizlilik','priority'=>'0.3','freq'=>'yearly'],['loc'=>'/kullanim-kosullari','priority'=>'0.3','freq'=>'yearly'],
            ['loc'=>'/topluluk-kurallari','priority'=>'0.3','freq'=>'yearly'],
        ];
        try {
            $pdo=Database::connection();
            $teams=$pdo->query('SELECT id,updated_at FROM teams WHERE status="active" ORDER BY updated_at DESC LIMIT 5000')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach($teams as $row) $urls[]=['loc'=>'/takim/'.(int)$row['id'],'lastmod'=>substr((string)$row['updated_at'],0,10),'priority'=>'0.6','freq'=>'weekly'];
            $players=$pdo->query('SELECT u.id,GREATEST(u.updated_at,p.updated_at) AS updated_at FROM users u INNER JOIN player_profiles p ON p.user_id=u.id WHERE u.status="active" AND p.visibility="public" ORDER BY updated_at DESC LIMIT 10000')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach($players as $row) $urls[]=['loc'=>'/kariyer/'.(int)$row['id'],'lastmod'=>substr((string)$row['updated_at'],0,10),'priority'=>'0.6','freq'=>'weekly'];
            $amateurs=$pdo->query('SELECT slug,updated_at FROM amateur_player_ads WHERE status="active" AND expires_at>=NOW() ORDER BY updated_at DESC LIMIT 10000')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach($amateurs as $row) $urls[]=['loc'=>'/amator-futbolcu/'.rawurlencode((string)$row['slug']),'lastmod'=>substr((string)$row['updated_at'],0,10),'priority'=>'0.7','freq'=>'weekly'];
            $referees=$pdo->query('SELECT slug,updated_at FROM referee_profiles WHERE status="active" ORDER BY updated_at DESC LIMIT 10000')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach($referees as $row) $urls[]=['loc'=>'/hakem/'.rawurlencode((string)$row['slug']),'lastmod'=>substr((string)$row['updated_at'],0,10),'priority'=>'0.7','freq'=>'weekly'];
            $coaches=$pdo->query('SELECT slug,updated_at FROM coach_profiles WHERE status="active" ORDER BY updated_at DESC LIMIT 10000')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach($coaches as $row) $urls[]=['loc'=>'/teknik-direktor/'.rawurlencode((string)$row['slug']),'lastmod'=>substr((string)$row['updated_at'],0,10),'priority'=>'0.7','freq'=>'weekly'];
            $news=$pdo->query('SELECT slug,updated_at FROM news WHERE status="published" AND published_at IS NOT NULL AND published_at<=NOW() ORDER BY published_at DESC LIMIT 10000')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach($news as $row) $urls[]=['loc'=>'/haber/'.rawurlencode((string)$row['slug']),'lastmod'=>substr((string)$row['updated_at'],0,10),'priority'=>'0.7','freq'=>'weekly'];
            $authors=$pdo->query('SELECT public_slug,updated_at FROM admins WHERE status="active" AND public_profile_enabled=1 AND public_slug IS NOT NULL AND public_slug<>"" ORDER BY updated_at DESC LIMIT 1000')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach($authors as $row) $urls[]=['loc'=>'/yazar/'.rawurlencode((string)$row['public_slug']),'lastmod'=>substr((string)$row['updated_at'],0,10),'priority'=>'0.5','freq'=>'weekly'];
        } catch(Throwable) {}
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach($urls as $item){
            echo "  <url><loc>".htmlspecialchars(AppConfig::appUrl().$item['loc'],ENT_XML1|ENT_QUOTES,'UTF-8')."</loc>";
            if(!empty($item['lastmod'])) echo '<lastmod>'.htmlspecialchars($item['lastmod'],ENT_XML1).'</lastmod>';
            echo '<changefreq>'.$item['freq'].'</changefreq><priority>'.$item['priority']."</priority></url>\n";
        }
        echo "</urlset>\n";
    }
}
