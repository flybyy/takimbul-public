<?php
use App\Core\Csrf;
use App\Models\SiteSetting;
require __DIR__ . '/../layouts/header.php';
?>
<main class="auth-shell section-space">
    <section class="auth-card compact">
        <span class="eyebrow">TEKRAR HOŞ GELDİN</span>
        <h1>Maça geri dön.</h1>
        <?php if(!empty($error)): ?><div class="alert alert-error auth-error-summary" role="alert" aria-live="polite"><?= htmlspecialchars((string)$error) ?></div><?php endif; ?>
        <form method="post" action="/giris" class="form-grid" data-submit-guard>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <label>E-posta veya kullanıcı adı<input type="text" name="identifier" autocomplete="username" required placeholder="ornek@takimbul.com.tr veya kullaniciadi"></label>
            <label>Şifre<span class="password-field"><input id="login-password" type="password" name="password" autocomplete="current-password" required><button class="password-toggle" type="button" data-password-toggle data-target="login-password" aria-label="Şifreyi göster" aria-pressed="false">Göster</button></span></label>
            <button class="btn btn-primary" type="submit" data-busy-text="Giriş Yapılıyor…">Giriş Yap</button>
        </form><a class="text-link auth-help-link" href="/sifremi-unuttum">Şifremi unuttum</a><?php if(SiteSetting::get('require_email_verification','0')==='1'): ?> · <a class="text-link auth-help-link" href="/e-posta-dogrulama-gonder">Doğrulama e-postasını tekrar gönder</a><?php endif; ?>
        <p class="auth-switch">Hesabın yok mu? <a href="/uye-ol">Ücretsiz üye ol</a></p>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
