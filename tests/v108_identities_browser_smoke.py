#!/usr/bin/env python3
import sys
from pathlib import Path
from playwright.sync_api import sync_playwright

root=Path(__file__).resolve().parents[1]
css=(root/'public/assets/css/app-v108.css').read_text(encoding='utf-8')
out=Path('/tmp/takimbul-v108-identities-browser');out.mkdir(parents=True,exist_ok=True)
viewports=[(320,760),(360,800),(390,844),(430,932),(768,1024),(1024,900),(1440,1000),(1920,1080)]

nav='''<header class="site-header v9-header v103-header-safe tb-v106-header"><div class="container v9-nav-wrap"><a class="brand-wordmark">takim<span>bul</span></a><nav class="v9-desktop-nav tb-main-nav"><a class="tb-nav-direct">Keşfet</a><a class="tb-nav-direct">Oyuncular</a><a class="tb-nav-direct">Takımlar</a><div class="tb-nav-cluster"><a>Profesyoneller</a><div class="tb-nav-cluster-menu"><a>Hakemler</a><a>Teknik Direktörler</a></div></div><a class="tb-nav-direct">Maç</a><a class="tb-nav-direct">Haberler</a></nav><div class="v9-header-actions"><a class="login-link">Giriş Yap</a><a class="btn btn-primary btn-small">Ücretsiz Başla</a></div></div></header>'''
register=nav+'''<main class="auth-shell section-space v10-register-shell"><section class="auth-card v10-register-card"><span class="eyebrow">TAKIMBUL'A KATIL</span><h1>Futbol ağındaki yerini al.</h1><form class="form-grid v10-register-form"><div class="form-row-2"><label>Ad Soyad<input></label><label>Kullanıcı adı<input></label></div><fieldset class="identity-picker"><legend>TakımBul'a nasıl katılıyorsun?</legend><p class="form-help">Birden fazla futbol kimliği seçebilirsin.</p><div class="identity-picker-grid"><label class="identity-choice"><input type="checkbox" checked>Halı Saha Oyuncusuyum</label><label class="identity-choice"><input type="checkbox">Amatör Futbolcuyum</label><label class="identity-choice"><input type="checkbox">Hakemim</label><label class="identity-choice"><input type="checkbox">Teknik Direktör / Antrenörüm</label><label class="identity-choice"><input type="checkbox">Takım / Kulüp Yetkilisiyim</label></div></fieldset><button class="btn btn-primary">Ücretsiz Hesap Aç</button></form></section></main>'''
ref_list=nav+'''<main class="container section-space professional-directory"><section class="v10-page-head"><div><span class="eyebrow">HAKEM AĞI</span><h1>Hakem Bul</h1><p>Şehir, klasman ve müsaitliğe göre hakem profillerini keşfet.</p></div><a class="btn btn-primary">Hakem Profilimi Oluştur</a></section><section class="panel professional-filters"><form class="form-row-5"><label>İl<select><option>Antalya</option></select></label><label>İlçe<select><option>Alanya</option></select></label><label>Klasman<input value="İl Hakemi"></label><label>Müsaitlik<select><option>Maç tekliflerine açık</option></select></label><button class="btn btn-dark">Filtrele</button></form></section><section class="professional-card-grid">'''+''.join(f'''<article class="panel professional-card"><div class="professional-card-head"><div class="professional-avatar"><span>{x}</span></div><div><span class="eyebrow">İL HAKEMİ</span><h2>{name}</h2><p>Alanya, Antalya</p></div></div><div class="professional-meta"><span>Maç tekliflerine açık</span><span class="pro-license-ok">Lisans doğrulandı</span></div><a class="btn btn-soft btn-small">Profili Gör</a></article>''' for x,name in [('A','Ahmet Yılmaz'),('M','Mehmet Kaya'),('S','Selin Demir')])+'''</section></main>'''
ref_show=nav+'''<main class="container section-space professional-profile"><section class="professional-profile-hero panel"><div class="professional-avatar professional-avatar-lg"><span>A</span></div><div><span class="eyebrow">HAKEM PROFİLİ</span><h1>Ahmet Yılmaz</h1><p>İl Hakemi · Alanya, Antalya</p><div class="professional-badges"><span>Maç tekliflerine açık</span><span class="pro-license-ok">Hakem lisansı doğrulandı</span></div></div><a class="btn btn-primary">Mesaj Gönder</a></section><div class="professional-profile-grid"><section class="panel"><span class="eyebrow">KARİYER</span><h2>Hakemlik bilgileri</h2><dl class="v10-definition-list"><div><dt>Başlangıç</dt><dd>2021</dd></div><div><dt>Klasman</dt><dd>İl Hakemi</dd></div></dl><h3>Görev aldığı organizasyonlar</h3><p>Yerel amatör ligler ve turnuvalar.</p></section><aside class="panel"><span class="eyebrow">HAKKINDA</span><h2>Profil özeti</h2><p>Futbol kurallarına ve maç yönetimine odaklanan hakem profili.</p></aside></div></main>'''
coach=nav+'''<main class="container section-space professional-profile"><section class="professional-profile-hero panel"><div class="professional-avatar professional-avatar-lg"><span>T</span></div><div><span class="eyebrow">TEKNİK DİREKTÖR</span><h1>Tarık Koç</h1><p>UEFA B · Antalya</p><div class="professional-badges"><span>Tekliflere açık</span><span class="pro-license-ok">Antrenör lisansı doğrulandı</span></div></div><a class="btn btn-primary">Mesaj Gönder</a></section><div class="professional-profile-grid"><section class="panel"><span class="eyebrow">KARİYER</span><h2>Teknik kariyer</h2><div class="career-row"><strong>2025/26 · Alanya FK</strong><p>Teknik Direktör · 22 maç · 14 galibiyet</p></div></section><aside class="panel"><span class="eyebrow">UZMANLIK</span><div class="professional-tags"><span>Genç oyuncu gelişimi</span><span>4-3-3</span><span>Geçiş oyunu</span></div></aside></div></main>'''
admin='''<body class="admin-shell-body"><header class="admin-shell-header"><div class="container admin-shell-bar"><a class="admin-shell-brand"><span class="brand-wordmark">takim<span>bul</span></span></a><nav class="admin-shell-nav"><a>Panel</a><a>Haberler</a><a>Kullanıcılar</a><a>Amatör İlanlar</a><a class="active">Hakem & Teknik Ekip</a><a>Moderasyon</a><a>Audit</a></nav></div></header><main class="container admin-shell-main"><section class="admin-page-head"><div><span class="eyebrow">PROFESYONEL KİMLİKLER</span><h1>Hakem & Teknik Ekip</h1><p>Public futbol kimliklerini ve lisans doğrulamalarını yönet.</p></div></section><div class="admin-kpi-grid"><article><span>Bekleyen Hakem</span><strong>3</strong></article><article><span>Bekleyen Teknik Ekip</span><strong>2</strong></article><article><span>Doğrulanmış Lisans</span><strong>7</strong></article><article><span>Toplam Profil</span><strong>18</strong></article></div><section class="panel admin-table-wrap"><div class="admin-section-head"><div><span class="eyebrow">HAKEMLER</span><h2>Hakem profilleri</h2></div></div><table class="admin-table"><thead><tr><th>Hakem</th><th>Konum</th><th>Klasman</th><th>Müsaitlik</th><th>Lisans</th><th>Durum</th><th></th></tr></thead><tbody><tr><td><strong>Ahmet Yılmaz</strong><small>@ahmet</small></td><td>Alanya, Antalya</td><td>İl Hakemi</td><td>available</td><td><span class="admin-status verified">verified</span></td><td><span class="admin-status pending">pending</span></td><td><a>İncele</a></td></tr></tbody></table></section></main></body>'''
form=nav+'''<main class="container section-space professional-form-page"><section class="v10-page-head"><div><span class="eyebrow">TEKNİK EKİP KİMLİĞİ</span><h1>Teknik Direktör Profilim</h1></div></section><form class="panel form-grid professional-form"><div class="form-row-2"><label>İl<select><option>Antalya</option></select></label><label>İlçe<select><option>Alanya</option></select></label></div><div class="form-row-2"><label>Unvan<input value="Teknik Direktör"></label><label>Lisans seviyesi<input value="UEFA B"></label></div><div class="career-editor"><div class="career-edit-row"><input value="2025/26"><input value="Alanya FK"><input value="Teknik Direktör"><input value="22"><input value="14"><input value="4"><input value="4"></div></div><div class="form-actions"><button class="btn btn-soft">Taslak Kaydet</button><button class="btn btn-primary">Moderasyona Gönder</button></div></form></main>'''
fixtures=[('register',register),('ref-list',ref_list),('ref-show',ref_show),('coach',coach),('coach-form',form),('admin',admin)]
fail=[]
with sync_playwright() as p:
    browser=p.chromium.launch(headless=True,executable_path='/usr/bin/chromium',args=['--no-sandbox','--disable-background-networking'])
    ctx=browser.new_context(viewport={'width':360,'height':800},device_scale_factor=1)
    page=ctx.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
    for w,h in viewports:
        page.set_viewport_size({'width':w,'height':h})
        for name,body in fixtures:
            markup='<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'+(body if body.lstrip().startswith('<body') else '<body class="v9-body">'+body+'</body>')+'</html>'
            page.set_content(markup,wait_until='domcontentloaded');page.add_style_tag(content=css);page.wait_for_timeout(20)
            dims=page.evaluate('()=>({sw:document.documentElement.scrollWidth,cw:document.documentElement.clientWidth,bsw:document.body.scrollWidth})')
            if max(dims['sw'],dims['bsw'])>dims['cw']+1: fail.append(f'{w}px {name}: horizontal overflow {dims}')
            if name=='register':
                n=page.locator('.identity-choice').count()
                if n<5: fail.append(f'{w}px register: identity choices missing ({n})')
                if w<=430:
                    box=page.locator('.identity-picker-grid').bounding_box()
                    if not box or box['width']>w-10: fail.append(f'{w}px register: identity picker collapsed {box}')
            if name in ('ref-list','ref-show','coach'):
                av=page.locator('.professional-avatar').first.bounding_box()
                if not av or av['width']>110 or av['height']>110: fail.append(f'{w}px {name}: avatar oversized {av}')
            if name=='admin' and w>=768:
                k=page.locator('.admin-kpi-grid').bounding_box()
                if not k or k['height']>180: fail.append(f'{w}px admin: KPI grid collapsed {k}')
            if name=='coach-form':
                inp=page.locator('.professional-form input').first.bounding_box()
                if not inp or inp['height']<38: fail.append(f'{w}px coach-form: browser-default input {inp}')
            if w in (360,1024,1440) and name in ('register','ref-show','admin'):
                page.screenshot(path=str(out/f'{name}-{w}.png'),full_page=True)
    if errors: fail.append('page errors: '+' | '.join(errors[:3]))
    page.close();ctx.close();browser.close()
if fail:
    print('FAIL v108_identities_browser_smoke')
    for x in fail: print('- '+x)
    sys.exit(1)
print(f'PASS v108_identities_browser_smoke ({len(viewports)*len(fixtures)} renders, screenshots: {out})')
