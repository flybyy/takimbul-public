#!/usr/bin/env python3
import sys
from pathlib import Path
from playwright.sync_api import sync_playwright
root=Path(__file__).resolve().parents[1]
css=(root/'public/assets/css/app-v1071.css').read_text(encoding='utf-8')
out=Path('/tmp/takimbul-v1071-visual-hotfix');out.mkdir(parents=True,exist_ok=True)
viewports=[(320,760),(390,844),(768,1024),(1024,900),(1440,1000),(1920,1080)]
nav='''<header class="site-header v9-header v103-header-safe tb-v106-header"><div class="container v9-nav-wrap"><a class="brand-wordmark">takim<span>bul</span></a><nav class="v9-desktop-nav tb-main-nav"><a class="tb-nav-direct">Keşfet</a><a class="tb-nav-direct">Oyuncular</a><a class="tb-nav-direct">Takımlar</a><a class="tb-nav-direct">Maç</a><a class="tb-nav-direct">Haberler</a></nav><form class="v10-header-search"><input placeholder="Oyuncu, takım, ilan..."><button><svg width="20" height="20" viewBox="0 0 24 24"><circle cx="10" cy="10" r="6"/></svg></button></form><div class="v9-header-actions"><a>Giriş Yap</a><a class="btn btn-primary btn-small">Ücretsiz Başla</a></div></div></header>'''
player=nav+'''<main class="container section-space amateur-detail-page v103-player-detail"><section class="amateur-profile-hero"><div class="amateur-profile-main"><div class="amateur-detail-avatar"><span>M</span></div><div><div class="amateur-detail-flags"><span>VİTRİN · TAKIM ARIYOR</span><span>BOŞTA</span></div><h1>MURAT SADIK DİNÇER <span class="tb-verified-badge"><svg width="17" height="17" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/></svg></span></h1><p>Forvet · Defans · Alanya, Antalya</p></div></div></section><section class="amateur-detail-grid"><div><section class="panel amateur-detail-block"><span class="eyebrow">TAKIMBUL DOĞRULANMIŞ</span><h2>Platform kariyer özeti</h2><div class="amateur-verified-stats"><div><strong>0</strong><small>Maç</small></div><div><strong>0</strong><small>Gol</small></div><div><strong>0</strong><small>Asist</small></div></div><a>Tam kariyer profilini aç <svg width="20" height="20" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></a></section></div><aside><section class="panel amateur-contact-panel"><span class="eyebrow">İLETİŞİM</span><h2>Futbolcuyla bağlantı kur</h2></section></aside></section></main>'''
admin_list='''<body class="admin-shell-body"><header class="admin-shell-header"><div class="container admin-shell-bar"><a class="admin-shell-brand"><span class="brand-wordmark">takim<span>bul</span></span></a><nav class="admin-shell-nav"><a>Panel</a><a>Haberler</a><a>Sponsorlar</a><a>Kullanıcılar</a><a class="active">Amatör İlanlar</a><a>Moderasyon</a><a>Audit</a><a>Reklamlar</a><a>Ayarlar</a><a>Ekip & Yetkiler</a></nav></div></header><main class="container admin-shell-main"><section class="admin-page-head"><div><span class="eyebrow">MODERASYON</span><h1>Amatör Oyuncu Profilleri</h1><p>Takım arayan ve kendini tanıtan oyuncuları incele.</p></div><a class="btn btn-soft">Oyuncu Vitrinini Aç</a></section><div class="admin-kpi-grid"><article><span>Bekleyen</span><strong>1</strong></article><article><span>Aktif</span><strong>0</strong></article><article><span>Takım Buldu</span><strong>0</strong></article><article><span>Reddedilen</span><strong>0</strong></article></div><form class="panel admin-filter-bar"><input placeholder="İsim, kullanıcı, e-posta, telefon, kulüp"><select><option>Tüm durumlar</option></select><select><option>Tüm iller</option></select><select><option>Tüm mevkiler</option></select><select><option>Tüm amaçlar</option></select><select><option>Tüm lisanslar</option></select><button class="btn btn-dark">Filtrele</button></form><section class="panel admin-table-wrap"><table class="admin-table"><thead><tr><th>Futbolcu</th><th>Konum</th><th>Mevki</th><th>Amaç</th><th>Lisans</th><th>Öz-beyan</th><th>Durum</th><th>Güncelleme</th></tr></thead><tbody><tr><td><strong>Murat Sadık Dinçer</strong><small>@murat</small></td><td>Alanya, Antalya</td><td>Forvet</td><td>both</td><td>free</td><td>3 M · 0 G · 0 A</td><td><span class="admin-status pending">pending</span></td><td>2026-09-05</td></tr></tbody></table></section></main></body>'''
staff='''<body class="admin-shell-body"><main class="container section-space admin-staff-page"><div class="section-head"><div><span class="eyebrow">EKİP & YETKİLER</span><h1>Yeni Ekip Üyesi</h1><p>Rol server-side yetki sınırıdır.</p></div></div><form class="panel form-card admin-staff-form"><div class="form-grid two-col"><label>Ad Soyad<input></label><label>Kullanıcı adı<input></label><label>E-posta<input></label><label>Rol<select><option>Yazar</option></select></label><label>Durum<select><option>Aktif</option></select></label><label>Yazar URL adı<input></label></div><label>İlk şifre<input type="password"></label><hr><h2>Public yazar profili</h2><div class="form-grid two-col"><label>Public unvan<input></label><label>Avatar URL / yol<input></label><label>Instagram<input></label><label>X<input></label></div><label>Biyografi<textarea></textarea></label><label class="check-row"><input type="checkbox"><span>Public yazar profilini yayınla</span></label><button class="btn btn-primary">Kaydet</button></form></main></body>'''
fixtures=[('player',player),('admin-list',admin_list),('staff',staff)]
fail=[]
with sync_playwright() as p:
    browser=p.chromium.launch(headless=True,executable_path='/usr/bin/chromium',args=['--no-sandbox','--disable-background-networking'])
    for w,h in viewports:
        for name,body in fixtures:
            page=browser.new_page(viewport={'width':w,'height':h})
            page.set_content('<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"></head>'+body+'</html>')
            page.add_style_tag(content=css);page.wait_for_timeout(50)
            dims=page.evaluate('()=>({sw:document.documentElement.scrollWidth,cw:document.documentElement.clientWidth})')
            if dims['sw']>dims['cw']+1: fail.append(f'{w}px {name}: horizontal overflow {dims}')
            if name=='player':
                for sel,max_size in [('.tb-verified-badge svg',24),('.amateur-detail-block svg',24)]:
                    b=page.locator(sel).bounding_box()
                    if not b or b['width']>max_size or b['height']>max_size: fail.append(f'{w}px {name}: oversized {sel} {b}')
                if page.locator('summary').count(): fail.append(f'{w}px player: raw summary marker remains in desktop nav')
            if name=='admin-list' and w>=1024:
                k=page.locator('.admin-kpi-grid').bounding_box(); f=page.locator('.admin-filter-bar').bounding_box()
                if not k or k['height']>150: fail.append(f'{w}px admin-list: KPI layout collapsed {k}')
                if not f or f['height']>150: fail.append(f'{w}px admin-list: filter layout collapsed {f}')
            if name=='staff':
                inp=page.locator('.admin-staff-form input').first.bounding_box()
                if not inp or inp['height']<38: fail.append(f'{w}px staff: inputs look browser-default {inp}')
            page.screenshot(path=str(out/f'{name}-{w}.png'),full_page=True)
            page.close()
    browser.close()
if fail:
    print('FAIL v1071_visual_hotfix_smoke')
    for x in fail: print('- '+x)
    sys.exit(1)
print(f'PASS v1071_visual_hotfix_smoke ({len(viewports)*len(fixtures)} renders, screenshots: {out})')
