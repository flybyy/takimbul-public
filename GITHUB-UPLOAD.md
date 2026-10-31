# GitHub Public Repository Yayını

Bu paket public GitHub repository için hazırlanmıştır.

## Yükleme

GitHub'da yeni bir repository oluştur:

- Visibility: **Public**
- `Add a README`: kapalı (README zaten pakette)
- `Add .gitignore`: kapalı
- `Choose a license`: kapalı (`LICENSE` zaten pakette)

Ardından:

```bash
git init
git add .
git commit -m "Initial public release of TakımBul"
git branch -M main
git remote add origin https://github.com/KULLANICI_ADIN/takimbul.git
git push -u origin main
```

## Public repo öncesi kontrol

```bash
git status
git ls-files | grep -E 'config/app\.php|\.env|storage/logs|public/uploads'
```

İkinci komut gerçek secret/runtime dosyası döndürmemelidir. `.gitkeep` dosyaları normaldir.

## Önerilen GitHub ayarları

- About: `Türkiye'nin amatör futbol ağı — oyuncular, takımlar, hakemler ve teknik ekipler için futbol platformu.`
- Website: `https://takimbul.com.tr/`
- Topics: `php`, `mysql`, `football`, `amateur-football`, `responsive-web`, `portfolio-project`
- Issues: ihtiyaca göre açık
- Wiki: kapalı tutulabilir

## Release etiketi

```bash
git tag -a v10.8-public -m "TakımBul V10.8 public portfolio release"
git push origin v10.8-public
```
