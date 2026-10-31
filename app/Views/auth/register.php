<?php
use App\Core\Csrf;
use App\Core\TurkeyLocation;
$errors = $errors ?? [];
$old = $old ?? [];
require __DIR__ . '/../layouts/header.php';
?>
<main class="auth-shell section-space v10-register-shell">
    <section class="auth-card v10-register-card">
        <span class="eyebrow">TAKIMBUL'A KATIL</span>
        <h1>Futbol ağındaki yerini al.</h1>
        <p>Oyuncu profilini oluştur, takım bul, rakip eşleştir ve maç geçmişini tek merkezde büyüt.</p>
        <?php if(isset($errors['form'])): ?><div class="flash flash-error auth-error-summary" role="alert" aria-live="polite"><div><?= htmlspecialchars((string)$errors['form'], ENT_QUOTES, 'UTF-8') ?></div></div><?php elseif($errors!==[]): ?><div class="auth-error-summary sr-only" role="alert" aria-live="polite">Formdaki işaretli alanları kontrol et.</div><?php endif; ?>
        <form method="post" action="/uye-ol" class="form-grid v10-register-form" data-submit-guard>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>"><label class="hp-field" aria-hidden="true">Web sitesi<input name="website" tabindex="-1" autocomplete="off"></label>
            <div class="form-row-2">
                <label>Ad / Görünen ad
                    <input name="display_name" required maxlength="100" autocomplete="name" value="<?= htmlspecialchars((string)($old['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <?php if(isset($errors['display_name'])): ?><small class="field-error"><?= htmlspecialchars((string)$errors['display_name']) ?></small><?php endif; ?>
                </label>
                <label>Kullanıcı adı
                    <input name="username" required minlength="3" maxlength="30" autocomplete="username" value="<?= htmlspecialchars((string)($old['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="futbolcu34">
                    <?php if(isset($errors['username'])): ?><small class="field-error"><?= htmlspecialchars((string)$errors['username']) ?></small><?php endif; ?>
                </label>
            </div>
            <div class="form-row-2">
                <label>Cep telefonu
                    <input name="phone" required inputmode="tel" autocomplete="tel" value="<?= htmlspecialchars((string)($old['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="05XX XXX XX XX">
                    <small>Şimdilik SMS doğrulaması yok. Numaran yalnızca sana ve yönetime görünür.</small>
                    <?php if(isset($errors['phone'])): ?><small class="field-error"><?= htmlspecialchars((string)$errors['phone']) ?></small><?php endif; ?>
                </label>
                <label>E-posta
                    <input type="email" name="email" required autocomplete="email" value="<?= htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <?php if(isset($errors['email'])): ?><small class="field-error"><?= htmlspecialchars((string)$errors['email']) ?></small><?php endif; ?>
                </label>
            </div>
            <div class="form-row-2">
                <label>Şifre
                    <span class="password-field"><input id="register-password" type="password" name="password" required minlength="10" autocomplete="new-password"><button class="password-toggle" type="button" data-password-toggle data-target="register-password" aria-label="Şifreyi göster" aria-pressed="false">Göster</button></span><small>En az 10 karakter, en az bir harf ve bir rakam.</small>
                    <?php if(isset($errors['password'])): ?><small class="field-error"><?= htmlspecialchars((string)$errors['password']) ?></small><?php endif; ?>
                </label>
                <label>Şifre tekrar
                    <span class="password-field"><input id="register-password-confirmation" type="password" name="password_confirmation" required minlength="10" autocomplete="new-password"><button class="password-toggle" type="button" data-password-toggle data-target="register-password-confirmation" aria-label="Şifre tekrarını göster" aria-pressed="false">Göster</button></span><small>Şifreni tekrar gir.</small>
                    <?php if(isset($errors['password_confirmation'])): ?><small class="field-error"><?= htmlspecialchars((string)$errors['password_confirmation']) ?></small><?php endif; ?>
                </label>
            </div>
            <div class="form-row-2" data-location-group>
                <label>İl<select name="province" required data-location-province><?= TurkeyLocation::provinceOptions((string)($old['province'] ?? '')) ?></select><?php if(isset($errors['province'])): ?><small class="field-error"><?= htmlspecialchars((string)$errors['province']) ?></small><?php endif; ?></label>
                <label>İlçe<select name="district" required data-location-district><?= TurkeyLocation::districtOptions((string)($old['province'] ?? ''),(string)($old['district'] ?? '')) ?></select><?php if(isset($errors['district'])): ?><small class="field-error"><?= htmlspecialchars((string)$errors['district']) ?></small><?php endif; ?></label>
            </div>
            <fieldset class="identity-picker"><legend>TakımBul’a nasıl katılıyorsun?</legend><p class="form-help">Birden fazla rolün varsa birden fazlasını seçebilirsin.</p>
                <?php $selectedIdentities=$old['identity_types']??['recreational_player']; if(!is_array($selectedIdentities))$selectedIdentities=[$selectedIdentities]; ?>
                <div class="identity-picker-grid">
                    <?php foreach([
                        'recreational_player'=>['Halı Saha Oyuncusuyum','Maç yap, müsaitlik aç, takım ve rakip bul.'],
                        'amateur_player'=>['Amatör Futbolcuyum','Kariyerini, kulüplerini ve istatistiklerini sergile.'],
                        'referee'=>['Hakemim','Hakem profilini oluştur ve maç tekliflerine açıl.'],
                        'coach'=>['Teknik Direktör / Antrenörüm','Teknik kariyerini sergile ve takımlarla bağlantı kur.'],
                        'club_official'=>['Takım / Kulüp Yetkilisiyim','Takım kur, kadro ve kulüp işlemlerini yönet.'],
                    ] as $identityType=>$identityCopy): ?>
                    <label class="identity-choice"><input type="checkbox" name="identity_types[]" value="<?= $identityType ?>" <?= in_array($identityType,$selectedIdentities,true)?'checked':'' ?>><span><strong><?= htmlspecialchars($identityCopy[0]) ?></strong><small><?= htmlspecialchars($identityCopy[1]) ?></small></span></label>
                    <?php endforeach; ?>
                </div><small>Birden Fazla Rol Kullanacağım: ilgili kartların hepsini seçebilirsin.</small>
                <?php if(isset($errors['identity_types'])): ?><small class="field-error"><?= htmlspecialchars((string)$errors['identity_types']) ?></small><?php endif; ?>
            </fieldset>
            <label class="v10-consent-check"><input type="checkbox" name="privacy_consent" value="1" <?= (($old['privacy_consent']??'')==='1')?'checked':'' ?> required><span><a href="/gizlilik" target="_blank" rel="noopener">Gizlilik Politikası</a> ve <a href="/kullanim-kosullari" target="_blank" rel="noopener">Kullanım Koşulları</a>'nı okudum ve kabul ediyorum.</span></label>
            <?php if(isset($errors['privacy_consent'])): ?><small class="field-error"><?= htmlspecialchars((string)$errors['privacy_consent']) ?></small><?php endif; ?>
            <button class="btn btn-primary" type="submit" data-busy-text="Hesap Oluşturuluyor…">Ücretsiz Hesap Aç</button>
        </form>
        <p class="auth-switch">Zaten hesabın var mı? <a href="/giris">Giriş yap</a></p>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
