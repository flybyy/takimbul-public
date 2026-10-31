# TakımBul v9 — Canlıya Alma Checklist

## 1. Domain, SSL ve hosting
- `takimbul.com.tr` A/AAAA kayıtlarını doğru sunucuya yönlendir.
- Let’s Encrypt/SSL aktif olmadan üyelik açma.
- Domain document root'unu projenin `public/` klasörüne ver.
- HTTPS doğrulandıktan sonra `public/.htaccess` içindeki canonical host 301 yönlendirmesini aç.
- PHP 8.1+ ve `pdo_mysql` aktif olmalı.
- DirectAdmin document root değiştirilemiyorsa paketi `domains/takimbul.com.tr/public_html` içine çıkar; kök `.htaccess` hassas klasörleri kapatıp istekleri `public/` front controller'ına yönlendirir.

## 2. Uygulama ayarı
- `config/app.example.php` → `config/app.php` kopyala.
- `app.url = https://takimbul.com.tr`, `app.env = production`, `app.debug = false` bırak.
- Gerçek MySQL kullanıcı/parolasını gir; `CHANGE_ME` bırakma.
- `storage/logs` klasörünü web sunucusunun yazabileceği ancak doğrudan web'den erişilemeyecek konumda tut.
- `php scripts/launch-check.php` çalıştır ve kritik `[!!]` kalmasın.

## 3. Veritabanı
- Sıfır kurulum: `database/schema.sql`.
- v6'dan yükseltme: `database/migrations/2026-08-31-launch-ready-seo.sql` dosyasını **bir kez** çalıştır.
- V8.1 yönetici hesabının `veribayt` kullanıcı adıyla aktif ve e-postası doğrulanmış olduğunu kontrol et. Parolayı kaynak dosyaya yazma; gerekirse `TAKIMBUL_ADMIN_PASSWORD` ortam değişkeniyle `php scripts/admin-bootstrap.php` çalıştır.
- Günlük/haftalık yedek politikasını hosting panelinden etkinleştir.
- Haftalık bakım için eski kayıtları temizle: `DELETE FROM auth_rate_limits WHERE updated_at < NOW() - INTERVAL 2 DAY;` ve `DELETE FROM user_tokens WHERE expires_at < NOW() - INTERVAL 7 DAY;`.

## 4. E-posta
- `noreply@takimbul.com.tr`, `destek@takimbul.com.tr` ve yönetici hesap kurtarma için `admin@takimbul.com.tr` posta kutularını oluştur.
- `mail.from` ayarını gerçek gönderici adresine eşleştir.
- Hostingde PHP `mail()` teslimatını test et. Teslimat zayıfsa yayın öncesinde SMTP sağlayıcılı bir mail taşıyıcısına geç.
- Gerçek bir test hesabıyla kayıt doğrulama, yeniden doğrulama e-postası ve şifre sıfırlamayı uçtan uca dene.

## 5. Google SEO / Search Console
- Google Search Console'da `takimbul.com.tr` mülkünü doğrula.
- Verilen HTML meta doğrulama kodunu `seo.google_site_verification` alanına gir.
- `https://takimbul.com.tr/robots.txt` ve `https://takimbul.com.tr/sitemap.xml` adreslerini tarayıcı/curl ile kontrol et.
- Search Console → Sitemaps alanından `/sitemap.xml` gönder.
- Ana sayfa, `/oyuncu-bul`, `/rakip-bul`, `/takimlar`, `/siralama`, en az bir takım ve bir oyuncu kariyer URL'sini URL Denetleme ile kontrol et.
- Giriş, mesaj, bildirim ve maç yönetimi sayfalarında `noindex` olduğunu kaynak koddan kontrol et.
- Filtreli URL'lerin canonical'ının sorgusuz ana sayfaya işaret ettiğini ve `noindex,follow` verdiğini kontrol et.

## 6. İçerik ve yerel SEO
- `/rehber` içindeki başlangıç rehberlerini gözden geçir; gerçek saha/organizasyon deneyimi, ekran görüntüsü veya özgün örneklerle zamanla zenginleştir.
- İçeriği olmayan yüzlerce il/ilçe landing page üretme. Bölgesel sayfa ancak o bölgede gerçek takım/ilan/maç verisi olduğunda açılmalı.
- Takım ve kullanıcıların isim/açıklama alanlarında spam anahtar kelime kullanımını moderasyonla engelle.

## 7. Analytics ve çerez
- GA4 kullanacaksan `analytics.ga4_id` değerini gir. Değer boşsa Google Analytics hiç yüklenmez.
- GA4 tanımlı olduğunda analitik script sadece ziyaretçi “Kabul Et” dedikten sonra yüklenir.
- Analitik tercihi ve KVKK/çerez metinlerini gerçek işletme/hukuk yapısına göre son kez kontrol et.

## 8. Hukuk ve topluluk güvenliği
- `/gizlilik`, `/kullanim-kosullari`, `/topluluk-kurallari` başlangıç metinlerini gerçek veri sorumlusu/işletme unvanı, iletişim, KVKK aydınlatma ve saklama politikasıyla hukuk danışmanına tamamlat.
- Kullanıcı, takım, ilan ve maçlarda “Şikâyet Et” akışını test et.
- `/admin/sikayetler` ve `/admin/scout-basvurulari` alanlarının normal kullanıcıda 403 verdiğini doğrula.

## 9. Performans ve mobil
- PageSpeed Insights/Lighthouse mobil testini ana sayfa + oyuncu bul + kariyer profili için çalıştır.
- Hedef: LCP ≤ 2.5 sn, INP < 200 ms, CLS < 0.1.
- Apache gzip/deflate ve statik dosya cache header'larını `curl -I` ile doğrula.
- Gerçek Android Chrome ve iPhone Safari'de alt navigasyon, formlar ve klavye açıldığında görünümü test et.

## 10. Son smoke test
- Kayıt → e-posta doğrulama → giriş → profil → takım → ilan → rakip teklif → maç → sonuç → puan → kariyer akışını canlı test verisiyle tamamla.
- Bir scout hesabını admin onayla; takip listesi ve gizli iletişim verilerinin açığa çıkmadığını kontrol et.
- `storage/logs` içinde PHP/PDO hatası olmadığını kontrol et.

## 11. Reklam Merkezi (v8)
- V7'den yükseltmede `database/migrations/2026-08-31-ad-manager.sql` dosyasını bir kez çalıştır.
- `/admin/reklamlar` alanının normal kullanıcıda 403 verdiğini kontrol et.
- Bir manuel test reklamı oluştur; başlangıç/bitiş, cihaz ve konum hedeflemesini kontrol et.
- Reklamın `Sponsorlu` ibaresiyle görünmesini, tıklamanın `/reklam/tikla/{id}` üzerinden hedef siteye 302 ile gitmesini doğrula.
- Gösterim/tıklama/CTR sayaçlarını kontrol et.
- AdSense kullanacaksan gerçek AdSense kodunu yalnızca `Harici Reklam Kodu` alanına yapıştır ve tarayıcı konsolunda CSP hatası olmadığını doğrula.
- Farklı harici reklam sağlayıcılarında sağlayıcının script/frame domainlerini `Bootstrap.php` ve `.htaccess` CSP izin listesine elle eklemeden yayına alma.
- Gerçek Google AdSense açıldığında Google'ın gerekli consent/privacy message yapılandırmasını ve KVKK/çerez metinlerini ayrıca tamamla; manuel TakımBul reklamları üçüncü taraf takip kodu kullanmadığı sürece bu harici kod akışından bağımsızdır.


## 12. V8.1 yönetim, profil ve reklam doğrulaması
- V8'den yükseltmede `database/migrations/2026-08-31-v81-professional-stabilization.sql` dosyasını **bir kez** çalıştır.
- Giriş ekranında hem e-posta hem `veribayt` kullanıcı adıyla oturum açılabildiğini doğrula.
- İlk canlı girişten sonra yönetici parolasını yalnızca yetkili kişilerin bildiği yeni güçlü bir parola ile yenile.
- `/admin` üzerinde kullanıcı/takım/ilan/reklam sayaçlarının veritabanıyla tutarlı olduğunu kontrol et.
- `/admin/kullanicilar` üzerinden test kullanıcıyı askıya al; mevcut oturumunun en geç 60 saniye içinde yetki kaybettiğini doğrula ve hesabı tekrar aktifleştir.
- `/profil/duzenle` ekranından kullanıcı adı, il/ilçe ve JPEG/PNG/WebP profil fotoğrafı kaydet; kariyer sayfasında fotoğrafın ve `@kullaniciadi` bilgisinin göründüğünü doğrula.
- `public/uploads/avatars` klasöründe PHP/PHTML/PHAR gibi dosyaların çalıştırılamadığını ve yalnız resim uzantılarının üretildiğini kontrol et.
- Ana sayfada giriş yapan test hesabının doğrulanmış maç/gol/asist/sezon puanının sabit örnek değer yerine gerçek veriyi gösterdiğini doğrula.
- `/admin/reklamlar` Teslimat sütununda admin cihaz/konumu için reklamın durumunu kontrol et.
- `/admin/reklamlar/{id}/onizle` ekranında farklı cihaz/il/ilçe değerleriyle reklam görünürlüğünü simüle et; önizlemenin gösterim sayacını artırmadığını doğrula.


## 13. V9 Matchday doğrulaması
- V8.1'den yükseltmede `database/migrations/2026-09-01-v9-matchday.sql` dosyasını **bir kez** çalıştır. Sıfır kurulumda migration çalıştırma; güncel `database/schema.sql` yeterlidir.
- `/musaitim` üzerinden test oyuncusu için 1-4 saatlik müsaitlik yayınla; ikinci yayınlamada önceki aktif kaydın kapanıp tek aktif kayıt kaldığını kontrol et.
- `/musait-oyuncular` sayfasında il/ilçe/mevki filtresini ve profil fotoğraflarını kontrol et. Kaptan/yardımcı kaptan hesabıyla davet gönder; oyuncu hesabıyla kabul ve ret akışını test et.
- `/rakip-bul/anlik` ekranında yönettiğin takım için açık rakip ilanlarının uyum skoruna göre sıralandığını ve `Maç Teklifi Gönder` akışının mevcut teklif sistemine bağlandığını doğrula.
- Yaklaşan bir maçta iki farklı takım üyesiyle `Geliyorum / Belirsizim / Gelemiyorum` cevaplarını test et. Maç saatinden sonra katılım değişikliğinin engellendiğini kontrol et.
- `/mac/{id}` Matchday ekranında Katılım, Kadro, Sonuç, Performans ve Akış bölümlerinin mevcut v4-v6 doğrulama sistemleriyle birlikte çalıştığını doğrula.
- `/kariyer/{id}` üzerinde TakımBul Değeri, sezon puanı, güven skoru, doğrulanmış istatistik, scout takibi ve video vitrini görünümünü kontrol et.
- `/kariyer/vitrin` üzerinden HTTPS YouTube/Instagram/TikTok bağlantısı ekle; desteklenmeyen domain ve HTTP URL'nin reddedildiğini doğrula.
- `/admin` üzerinde online kullanıcı, bugünkü maç, bekleyen sonuç, aktif müsaitlik ve bekleyen davet sayaçlarını kontrol et.
- `/admin/ayarlar` üzerinden duyuru, operasyon mesajı ve ana sayfa modül anahtarlarını değiştir; public tarafta değişikliklerin yansıdığını doğrula.
- `php scripts/launch-check.php` çıktısında v9 route ve migration kontrollerinin `[OK]` olduğunu doğrula.
- Rate-limit testinde normal kullanımın engellenmediğini; hızlı tekrarlanan müsaitlik, davet ve video yazımlarının HTTP 429 ile sınırlandığını doğrula.

## 14. V9.1 profesyonel ana sayfa, reklam ve ayrı admin doğrulaması

- V9'dan yükseltmede `database/migrations/2026-09-01-v91-home-ads-admin.sql` dosyasını **bir kez** çalıştır. Sıfır kurulumda yalnızca güncel `database/schema.sql` kullan.
- `/admin/giris` girişinin ayrı `admins` tablosunu kullandığını doğrula. Yönetici hesabıyla `/giris` üzerinden oyuncu oturumu açılmamalı.
- Önce normal kullanıcıyla giriş yap, ardından `/admin/giris` üzerinden yönetici girişi yap; kullanıcı oturumunun kapanmış olduğunu doğrula. Tersini de test et.
- Admin hesabının kariyer profili, takım üyeliği veya oyuncu istatistiği üretmediğini doğrula.
- `/admin/ayarlar` üzerinde **E-posta doğrulaması zorunlu** anahtarının varsayılan kapalı olduğunu doğrula. Yeni üyeyi kayıt sonrası doğrulama maili beklemeden içeri al; şifre sıfırlamayı ayrıca test et.
- Ana sayfada boş DB metriklerinin büyük `0` yerine `—` gösterdiğini; veri eklendikten sonra gerçek sayıya döndüğünü kontrol et.
- `/admin/reklamlar` Teslimat Testi'nde `Masaüstü/Mobil + Antalya/Alanya` gibi kombinasyonları dene.
- Türkçe karakterli hedeflerde (`İstanbul`, `Kadıköy`) reklamın normalize edilmiş eşleşmeyle teslim edildiğini doğrula.
- Yerel hedef uyuşmuyorsa genel Türkiye reklamının fallback olarak göründüğünü doğrula.
