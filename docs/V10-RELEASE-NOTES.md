# TakımBul v10 — Ultimate Match Platform

## Ana hedef
v10, TakımBul'u yalnız ilan gösteren bir site olmaktan çıkarıp oyuncu-takım eşleşmesi, detaylı yönetim, güvenli telefonlu onboarding ve premium reklam teslimatı olan tam bir amatör futbol ağına dönüştürür.

## Yeni üyelik ve Hesabım
- Yeni kayıtlarda ad, kullanıcı adı, **Türkiye cep telefonu**, e-posta, şifre, il, ilçe ve gizlilik/koşul onayı zorunludur.
- Telefonlar `+905XXXXXXXXX` biçimine normalize edilir; 0212 gibi sabit hatlar kabul edilmez.
- SMS doğrulaması bu sürümde yoktur.
- Telefon public oyuncu/takım/scout sorgularına verilmez; yalnız hesap sahibi ve ayrı admin hesabı görür.
- `/hesabim` üzerinden kullanıcı telefon ve e-postasını güvenli biçimde güncelleyebilir.
- E-posta doğrulaması varsayılan olarak kapalı kalır; admin ayarlarından tekrar açılabilir.

## Takım Bul
- `/takim-bul`: açık takım ihtiyaçlarını il, ilçe, mevki ve seviyeye göre filtreler.
- Deterministik `% uyum` puanı konum, mevki, son aktiflik ve seviye üzerinden açıklanabilir biçimde hesaplanır.
- `/takim-ariyorum`: oyuncunun takım arama profili, tercihleri ve müsaitlik açıklaması.
- Takım kaptanı/yöneticisi takım arayan oyuncuları görür ve doğrudan takım daveti gönderebilir.
- Davetler bekleyen/kabul/reddedildi durumlarına sahiptir; kabul edilince takım üyeliği transaction içinde oluşturulur.
- Davet gönderiminde CSRF, yetki kontrolü, duplicate kontrolü ve rate limit vardır.

## Admin kullanıcı operasyonu
- `/admin/kullanicilar`: kullanıcı adı, e-posta, telefon, şehir, durum ve son aktifliğe göre yönetim listesi/filtreleri.
- `/admin/kullanici/{id}`: iletişim, konum, profil, takım, kariyer, ilan/başvuru/şikâyet özeti ve son aktiflik.
- Admin kullanıcı iletişimini güvenli güncelleyebilir, hesabı askıya alıp aktifleştirebilir, iç yönetim notu ekleyebilir ve şifre sıfırlama bağlantısı üretebilir.
- Admin hesabı public kullanıcı olmaya devam etmez; `/admin/giris` ve public `/giris` oturumları tamamen ayrıdır.

## Global arama
- `/ara?q=` oyuncu, takım, açık oyuncu ilanı ve rakip ilanını tek ekranda arar.
- Masaüstü header içine hızlı arama eklendi.
- Arama ve kişisel Takım Bul/Hesabım sayfaları `noindex` tutulur.

## Reklam Merkezi v10
- **`home_top`**: ana sayfanın ilk görünümünde premium sponsor alanı.
- **`welcome_takeover`**: uygun kampanya varsa ziyaretçinin ilk girişinde, tarayıcı oturumu başına bir kez açılan kapatılabilir sponsor paneli.
- `home_feed`, `home_sidebar`, `mobile_feed` ve diğer eski slotlar çalışmaya devam eder.
- Admin reklam formu yeni slotları, cihaz/il/ilçe/tarih hedeflemesini, önizlemeyi ve gösterim/tıklama/CTR ölçümünü destekler.
- `/admin/ayarlar` içinden ilk ziyaret sponsor paneli açılıp kapatılabilir.
- `scripts/launch-check.php`, ilk ekran reklam özelliğini ve aktif `home_top`/`welcome_takeover` kampanyası olup olmadığını ayrıca raporlar.

## Production güvenliği
- v10 migrationı mevcut verileri silmez ve v9.1.2 üstüne eklemeli çalışır.
- MySQL/MariaDB shared-host uyumluluğu için kolon ekleme `information_schema + PREPARE` ile repeat-safe yapılır.
- Native PDO (`ATTR_EMULATE_PREPARES=false`) için tekrarlanan named placeholder sorguları temizlendi. Bu düzeltme özellikle public/admin giriş sorgularında production `HY093` riskini kaldırır.
- Yeni POST işlemleri CSRF korumalıdır.
- Public oyuncu sorguları telefonu seçmez.
- v10 tabloları migration öncesi yoksa admin ana dashboardundaki yeni sayaçlar güvenli biçimde `0` döner; tüm paneli 500'e düşürmez.

## Mevcut v9.1.2 kurulumunu yükseltme
1. Mevcut `config/app.php` ve `public/uploads/` içeriğini koruyun.
2. v10 dosyalarını sunucuya yükleyin.
3. phpMyAdmin'de **yalnızca bir kez** `database/migrations/2026-09-01-v10-ultimate-platform.sql` çalıştırın.
4. `php scripts/launch-check.php` çalıştırın.
5. `/admin/giris` ile yönetime girip en az bir `home_top` reklamı oluşturun. İlk ziyaret paneli isteniyorsa ayrıca `welcome_takeover` kampanyası oluşturun.
6. Gerçek bir yeni hesapla kayıt → profil → Hesabım → Takım Bul → çıkış/giriş akışını test edin.

**Mevcut veritabanında `database/schema.sql` çalıştırmayın.** `schema.sql` yalnızca sıfır/boş kurulum içindir.
