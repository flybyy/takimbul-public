# TakımBul v9 — Matchday Professional

TakımBul v9, v8.1 tabanını koruyarak platformu ilan sitesi görünümünden gerçek bir amatör futbol operasyon ürününe taşır. Sürüm; canlı oyuncu müsaitliği, anlık rakip eşleştirme, maç katılımı, Matchday, Kariyer 2.0, güven skoru, video vitrini, canlı operasyon yönetimi ve yeni ürün tasarım sistemini birlikte getirir.

## Ürün tasarımı

- Kırık beyaz, kömür ve saha yeşili tabanlı yeni v9 tasarım sistemi.
- Büyük gradient, yapay demo metriği ve emoji odaklı görsel dil kaldırıldı.
- `takimbul` wordmark marka kimliği korunur; çekirdek yüzeylerde özel inline SVG ikon seti kullanılır.
- Mobilde `Keşfet · Bul · Maçlar · Mesajlar · Profil` alt navigasyonu ve merkez `Bul` action-sheet bulunur.
- Ana sayfa giriş yapan ve yapmayan kullanıcı için ayrı ürün akışı sunar.
- Ana sayfa sayaçları yalnız gerçek veritabanı kayıtlarından gelir; boş veri durumları uydurma rakam üretmez.

## Müsaitim / canlı oyuncu havuzu

- Oyuncu başlangıç-bitiş zamanı, il, ilçe, mevki, mesafe tercihi ve kısa not ile müsaitlik yayınlayabilir.
- Kullanıcı başına aynı anda tek aktif müsaitlik tutulur.
- `/musait-oyuncular` konum/mevki filtresiyle aktif ve yaklaşan müsaitlikleri listeler.
- Takım kaptanı veya yardımcı kaptanı müsait oyuncuya takım adına maç daveti gönderebilir.
- Oyuncu daveti kabul veya reddedebilir; kabul edilen müsaitlik `matched` durumuna geçer.
- Müsait oyuncu kartları gerçek profil fotoğrafını kullanır.
- Yayın ve davet işlemlerinde CSRF + kullanıcı/IP tabanlı rate-limit uygulanır.

## Anlık Rakip Bul

- `/rakip-bul/anlik` yönettiğin takım için açık rakip ilanlarını uyum skoruna göre sıralar.
- Skor yapay zekâ iddiası taşımaz; deterministik olarak il/ilçe, maç formatı, seviye ve hedef saate göre hesaplanır.
- Sonuçlar `Çok uyumlu`, `Uygun`, `Değerlendirilebilir`, `Düşük uyum` etiketleriyle açıklanır.
- Eşleşmeden mevcut çift taraflı maç teklif akışına geçilir.

## Matchday + katılım

- Takım üyeleri maç başlamadan `Geliyorum`, `Belirsizim`, `Gelemiyorum` durumunu seçebilir.
- Maç başladıktan sonra katılım cevabı kilitlenir.
- Maç odası zaman ve duruma göre `pre_match`, `matchday`, `live`, `result_pending`, `disputed`, `completed` modlarına geçer.
- Tek sayfada Katılım, Kadro, Sonuç, Performans ve Maç Akışı bulunur.
- Var olan çift kaptanlı skor doğrulama, gol/asist, oyuncu puanı ve MVP sistemi korunur.

## Kariyer 2.0

- Oyuncu dosyasında TakımBul Değeri, sezon puanı, doğrulanmış maç, gol, asist, MVP ve son maç formu birlikte görünür.
- Eski scout `Takibe Al` ve takım `Takımıma Davet Et` akışları korunur.
- Güven skoru 0-100 aralığında yalnız platform içi doğrulanmış aktivite ve katılım davranışından hesaplanır; öznel yıldız/yorum puanı değildir.
- Video vitrini en fazla altı aktif YouTube, Instagram veya TikTok HTTPS bağlantısı destekler.
- Video ekleme/silme sahiplik + CSRF ile korunur; ekleme rate-limitlidir.

## Yönetim Merkezi v9

`/admin` artık operasyon odaklıdır:

- online kullanıcılar (son 5 dakika),
- bugünkü maçlar,
- sonuç onayı bekleyen maçlar,
- aktif müsaitlikler,
- bekleyen müsaitlik davetleri,
- kullanıcı/takım/ilan/reklam/şikâyet/scout sayaçları,
- son kullanıcılar ve reklamlar,
- PHP, PDO MySQL, DB bağlantısı, log yazılabilirliği ve ortam sağlık göstergeleri.

`/admin/ayarlar` üzerinden:

- duyuru bandı,
- destek e-postası,
- ana sayfa Müsaitim modülü,
- Anlık Rakip Bul modülü,
- operasyon/bakım mesajı

yönetilebilir. Operasyon mesajı boş değilse site üstünde görünür.

## SEO

- `/musait-oyuncular` public ve sitemap'e dahildir.
- `/musaitim`, `/rakip-bul/anlik`, `/kariyer/vitrin` işlemsel/kişisel sayfalar `noindex` kapsamındadır.
- robots/sitemap, canonical, Open Graph, Twitter Card ve JSON-LD altyapısı korunur.

## Veritabanı

V9 migration:

`database/migrations/2026-09-01-v9-matchday.sql`

Eklenenler:

- `users.last_seen_at`
- `player_availabilities`
- `availability_invites`
- `match_attendance`
- `player_videos`
- `site_settings`

Migration tekrar çalıştırmaya karşı idempotent hazırlanmıştır. Sıfır kurulumda güncel `database/schema.sql` tüm v9 yapısını içerir.

## Yükseltme

### V8.1 → V9

1. Dosya ve veritabanı yedeği al.
2. V9 dosyalarını yükle.
3. `database/migrations/2026-09-01-v9-matchday.sql` dosyasını **bir kez** çalıştır.
4. `config/app.php` dosyanı yeni paketle ezme.
5. `/admin/ayarlar`, `/musaitim`, `/musait-oyuncular`, `/rakip-bul/anlik` ve bir `/mac/{id}` sayfasını smoke-test et.

### V8 → V9

Önce `2026-08-31-v81-professional-stabilization.sql`, ardından `2026-09-01-v9-matchday.sql` çalıştır.

### Sıfır kurulum

Yalnız güncel `database/schema.sql` yeterlidir; eski migration dosyalarını ayrıca çalıştırma.

## Yönetici güvenliği

V8.1'de tanımlanan `veribayt` yönetici kullanıcı adı korunur. İlk parola kaynak kodda düz metin olarak bulunmaz; yalnız `password_hash` saklanır. Canlıya çıktıktan sonra yönetici parolasını periyodik olarak değiştirmek önerilir.

## Test kapsamı

V9; mevcut v1-v8.1 regresyon testleri ile yeni availability, rival matcher, Matchday, trust/video, admin operations, SEO, design, polish, write rate-limit ve launch sözleşme testlerini birlikte taşır. Teslim ZIP'i oluşturulduktan sonra testler paketten yeniden açılmış temiz kopyada tekrar çalıştırılmalıdır.
