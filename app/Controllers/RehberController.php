<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Core\View;

final class RehberController
{
    private const ARTICLES=[
        'hali-saha-takimi-nasil-kurulur'=>[
            'title'=>'Halı Saha Takımı Nasıl Kurulur?','seoTitle'=>'Halı Saha Takımı Nasıl Kurulur? 7 Adımda Takım Kurma | TakımBul',
            'seoDescription'=>'Halı saha takımı kurmak için oyuncu sayısı, mevki dağılımı, kaptanlık, rakip bulma ve maç planlama adımlarını öğren.',
            'intro'=>'Düzenli maç yapan bir takım kurmak, sadece WhatsApp grubuna isim yazmaktan fazlasıdır. Doğru kadro dengesi ve net sorumluluklar takımın devamlılığını sağlar.',
            'sections'=>[
                ['1. Maç formatını belirle','5v5, 6v6, 7v7 veya 8v8 oynayacağını netleştir. Kalıcı kadro sayısını buna göre oluştur ve her maç için birkaç yedek planla.'],
                ['2. Mevkileri dengeli dağıt','Kaleci, savunma, orta saha ve hücum rollerini baştan belirlemek maç günü eksik mevki sürprizlerini azaltır.'],
                ['3. Kaptan ve yardımcı kaptan seç','Maç teklifi, kadro ve skor doğrulama gibi işlemler için sorumluluk sahibi iki yönetici belirle.'],
                ['4. Takım profilini tamamla','Şehir, ilçe, seviye ve takım açıklamasını doğru gir. Rakipler böylece sana uygun olup olmadığını daha hızlı anlar.'],
                ['5. Eksik oyuncuları ilanla tamamla','Maç günü eksiğin varsa mevki, tarih ve lokasyonu açıkça belirten ilan aç.'],
                ['6. Seviyene uygun rakip bul','Sadece kazanabileceğin değil, dengeli ve keyifli maç çıkaracağın rakipleri tercih et.'],
                ['7. Maç verisini doğrula','Skor ve bireysel istatistikleri iki kaptanın onayıyla kaydet; zamanla takım ve oyuncu profilleri güvenilir hale gelir.'],
            ]
        ],
        'eksik-oyuncu-nasil-bulunur'=>[
            'title'=>'Halı Saha Maçına Eksik Oyuncu Nasıl Bulunur?','seoTitle'=>'Halı Saha Maçına Eksik Oyuncu Nasıl Bulunur? | TakımBul',
            'seoDescription'=>'Halı saha maçına kaleci, defans, orta saha veya forvet eksiğini hızlı ve doğru şekilde bulmak için ilan hazırlama rehberi.',
            'intro'=>'Eksik oyuncu ilanında en önemli şey, oyuncunun karar vermek için ihtiyaç duyduğu bilgileri tek ekranda vermektir.',
            'sections'=>[
                ['Mevkiyi net yaz','“Oyuncu lazım” yerine “Kaleci aranıyor” veya “sol kanat/forvet aranıyor” gibi net ol.'],
                ['Tarih ve sahayı belirt','Maç saati, ilçe ve saha bilgisini eksiksiz ekle. Son dakika iptallerini azaltır.'],
                ['Seviyeyi dürüst seç','Eğlence, orta veya rekabetçi seviyeyi gerçekçi belirt.'],
                ['Ücret bilgisini gizleme','Kişi başı saha ücretini biliyorsan ilanda göster.'],
                ['Başvuruyu hızlı yanıtla','Oyuncu başvurduğunda kabul veya ret yanıtını geciktirme; dolan kadroyu kapat.'],
            ]
        ],
        'hali-saha-rakip-nasil-bulunur'=>[
            'title'=>'Halı Saha İçin Rakip Takım Nasıl Bulunur?','seoTitle'=>'Halı Saha Rakip Takım Nasıl Bulunur? Maç Ayarlama Rehberi | TakımBul',
            'seoDescription'=>'Halı saha için seviyene ve konumuna uygun rakip takım bulma, maç teklifi gönderme ve sonucu doğrulama adımları.',
            'intro'=>'İyi rakip eşleşmesi; yakın lokasyon, uyumlu seviye ve net maç koşullarının bir araya gelmesidir.',
            'sections'=>[
                ['Konum ve saati netleştir','Önce saha veya ilçe ile maç tarihini belirle; belirsiz ilanlar daha az dönüş alır.'],
                ['Maç formatını doğru seç','5v5 ile 8v8 aynı kadro ve oyun temposuna sahip değildir. Rakip ararken formatı baştan belirt.'],
                ['Seviye eşleşmesine dikkat et','Çok dengesiz maçlar iki takım için de kötü deneyim yaratır.'],
                ['Teklifi platform üzerinden gönder','Teklif kaydı, hangi takımın hangi ilana başvurduğunu ve eşleşmenin durumunu takip etmeyi kolaylaştırır.'],
                ['Maç sonrası skoru iki kaptan doğrulasın','Doğrulanmış sonuçlar takım ve oyuncu kariyerini daha güvenilir hale getirir.'],
            ]
        ],
    ];

    public function index(): void { View::render('guide/index',['title'=>'Halı Saha ve Amatör Futbol Rehberi','articles'=>self::ARTICLES,'seoTitle'=>'Halı Saha ve Amatör Futbol Rehberi | TakımBul','seoDescription'=>'Takım kurma, eksik oyuncu bulma, rakip bulma ve amatör futbol kariyeri için pratik TakımBul rehberleri.']); }
    public function show(array $params): void {
        $slug=(string)($params['slug']??''); $article=self::ARTICLES[$slug]??null;
        if($article===null){http_response_code(404);View::render('errors/404',['title'=>'Sayfa Bulunamadı']);return;}
        View::render('guide/show',$article+['slug'=>$slug]);
    }
}
