<?php
use App\Core\Csrf;
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#171a18"><link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="icon" href="/favicon.ico" sizes="any"><link rel="icon" href="/favicon-32x32.png" type="image/png" sizes="32x32"><link rel="apple-touch-icon" href="/apple-touch-icon.png"><title>Yönetici Girişi · TakımBul</title>
<link rel="stylesheet" href="/assets/css/app-v108.css?v=108">
</head>
<body class="admin-login-body">
<main class="admin-login-shell">
<section class="admin-login-card">
<a class="brand-wordmark" href="/" aria-label="TakımBul ana sayfa">takim<span>bul</span></a>
<div class="admin-login-heading"><span>YÖNETİM ALANI</span><h1>Operasyon paneli</h1><p>Bu giriş normal TakımBul kullanıcı hesaplarından tamamen ayrıdır.</p></div>
<?php if(!empty($error)):?><div class="alert alert-error"><?= htmlspecialchars((string)$error) ?></div><?php endif;?>
<form method="post" action="/admin/giris" class="form-grid">
<input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(),ENT_QUOTES,'UTF-8') ?>">
<label>Yönetici kullanıcı adı veya e-posta<input name="identifier" autocomplete="username" required autofocus></label>
<label>Şifre<input type="password" name="password" autocomplete="current-password" required></label>
<button class="btn btn-primary" type="submit">Yönetim Paneline Gir</button>
</form>
<a class="admin-back-link" href="/">← TakımBul'a dön</a>
</section>
</main>
</body></html>
