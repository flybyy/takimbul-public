#!/usr/bin/env python3
import sys
from pathlib import Path
from playwright.sync_api import sync_playwright

root=Path(__file__).resolve().parents[1]
css=(root/'public/assets/css/app.css').read_text(encoding='utf-8')
out=Path('/tmp/takimbul-v107-browser-smoke');out.mkdir(parents=True,exist_ok=True)
viewports=[(320,760),(360,800),(390,844),(430,932),(768,1024),(1024,900),(1440,1000),(1920,1080)]

badge='''<span class="tb-verified-badge" role="img" aria-label="TakımBul tarafından doğrulanmış profil" title="TakımBul tarafından doğrulanmış profil"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M7.8 12.2 10.5 15l5.8-6.2"></path></svg></span>'''
staff='''<span class="tb-staff-badge" role="img" aria-label="Resmi TakımBul hesabı"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M7.8 12.2 10.5 15l5.8-6.2"></path></svg></span>'''

player=f'''<main class="container section-space"><section class="amateur-profile-hero panel"><div class="amateur-profile-main"><div class="amateur-detail-avatar"><span>M</span></div><div><span class="eyebrow">OYUNCU DOSYASI</span><h1>Murat Dinçer {badge}</h1><p>Kanat · Alanya, Antalya</p><div class="amateur-profile-facts"><span>Sağ ayak</span><span>Aktif lisans</span><span>Vitrin · Takım arıyor</span></div></div></div></section></main>'''

news=f'''<main class="container section-space news-article"><header class="news-article-head"><div class="tb-sponsored-disclosure">SPONSORLU İÇERİK · Vitoria Sport</div><span class="eyebrow">AMATÖR FUTBOL</span><h1>Alanya amatör futbolunda yeni sezon hazırlıkları başladı</h1><p>Kulüpler, oyuncular ve organizasyonlardan güncel gelişmeler.</p><div class="news-byline"><span>Yazar: <a href="#">Murat Dinçer</a>{staff}<small>TakımBul Editörü</small></span></div></header><article class="news-article-body"><p>Haber metni gerçek bir editoryal sayfa gibi kompakt ve okunaklı görünmelidir.</p></article><aside class="tb-news-sponsor-block"><div class="sponsor-placeholder">LOGO</div><div><small>HABER SPONSORU</small><strong>Vitoria Sport</strong><p>Amatör futbol ve spor organizasyonları.</p></div><a class="btn btn-secondary" href="#">Sponsor Sitesi</a></aside></main>'''

author=f'''<main class="container section-space tb-author-page"><section class="panel tb-author-hero"><div class="tb-author-avatar"><span>M</span></div><div><span class="eyebrow">TAKIMBUL YAZARI</span><h1>Murat Dinçer {staff}</h1><strong>TakımBul Editörü</strong><p>Amatör futbol, oyuncu profilleri ve yerel futbol organizasyonları üzerine içerikler.</p><div class="tb-author-links"><a>Instagram</a><a>X</a></div></div><div class="tb-author-count"><strong>24</strong><span>yayın</span></div></section></main>'''

def admin_nav(role):
    base=['Panel','Haberler']
    if role in ('authorized','super_admin'): base += ['Sponsorlar','Kullanıcılar','Amatör İlanlar','Moderasyon','Audit']
    if role=='super_admin': base += ['Reklamlar','Ayarlar','Ekip & Yetkiler']
    links=''.join(f'<a href="#">{x}</a>' for x in base)
    label={'writer':'Yazar','authorized':'Yetkili','super_admin':'Süper Yönetici'}[role]
    return f'''<header class="admin-shell-header"><div class="container admin-shell-bar"><a class="admin-shell-brand"><span class="brand-wordmark">takim<span>bul</span></span><small>{label} Paneli</small></a><nav class="admin-shell-nav">{links}</nav><div class="admin-shell-account"><div><strong>Test {label}</strong><small>{label}</small></div><button>Çıkış</button></div></div></header><main class="container section-space"><section class="v9-page-head"><div><span class="v9-eyebrow">YÖNETİM</span><h1>{label} görünümü</h1><p>Rolün izin verdiği yönetim alanları.</p></div></section></main>'''

fixtures=[('player',player,None),('news',news,None),('author',author,None),('admin-writer',admin_nav('writer'),'writer'),('admin-authorized',admin_nav('authorized'),'authorized'),('admin-super',admin_nav('super_admin'),'super_admin')]
fail=[]
with sync_playwright() as p:
    browser=p.chromium.launch(headless=True,executable_path='/usr/bin/chromium',args=['--no-sandbox','--disable-background-networking'])
    for width,height in viewports:
        for name,body,role in fixtures:
            ctx=browser.new_context(viewport={'width':width,'height':height},device_scale_factor=1)
            page=ctx.new_page();errors=[];page.on('pageerror',lambda e,b=errors:b.append(str(e)))
            try:
                page.set_content(f'<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body class="v9-body admin-shell-body">{body}</body></html>',wait_until='domcontentloaded')
                page.add_style_tag(content=css);page.wait_for_timeout(80)
                dims=page.evaluate('() => ({sw:document.documentElement.scrollWidth,cw:document.documentElement.clientWidth,bsw:document.body.scrollWidth})')
                if max(dims['sw'],dims['bsw'])>dims['cw']+1: fail.append(f'{width}px {name}: horizontal overflow {dims}')
                if errors: fail.append(f'{width}px {name}: page error '+ ' | '.join(errors[:2]))
                if name=='player':
                    b=page.locator('.tb-verified-badge').bounding_box()
                    if not b or b['width']>22 or b['height']>22: fail.append(f'{width}px player: verified badge size bad {b}')
                if name=='news':
                    if page.locator('.tb-sponsored-disclosure').count()!=1: fail.append(f'{width}px news: disclosure missing')
                    sb=page.locator('.tb-news-sponsor-block').bounding_box()
                    if not sb or sb['width']<min(260,width-50): fail.append(f'{width}px news: sponsor block collapsed {sb}')
                if name=='author':
                    if page.locator('.tb-staff-badge').count()!=1: fail.append(f'{width}px author: staff badge missing')
                if role:
                    text=page.locator('.admin-shell-nav').inner_text()
                    if role=='writer' and any(x in text for x in ['Kullanıcılar','Reklamlar','Ayarlar','Ekip & Yetkiler','Audit','Sponsorlar']): fail.append(f'{width}px writer: forbidden nav visible: {text}')
                    if role=='authorized' and (any(x in text for x in ['Reklamlar','Ayarlar','Ekip & Yetkiler']) or not all(x in text for x in ['Sponsorlar','Kullanıcılar','Audit'])): fail.append(f'{width}px authorized: nav mismatch: {text}')
                    if role=='super_admin' and not all(x in text for x in ['Sponsorlar','Kullanıcılar','Audit','Reklamlar','Ayarlar','Ekip & Yetkiler']): fail.append(f'{width}px super: nav incomplete: {text}')
                page.screenshot(path=str(out/f'{name}-{width}.png'),full_page=True)
            except Exception as exc: fail.append(f'{width}px {name}: browser exception {exc}')
            finally: page.close();ctx.close()
    browser.close()
if fail:
    print('FAIL v107_browser_smoke')
    for x in fail: print('- '+x)
    sys.exit(1)
print(f'PASS v107_browser_smoke ({len(viewports)*len(fixtures)} responsive renders, screenshots: {out})')
