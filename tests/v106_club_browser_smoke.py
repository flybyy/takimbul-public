#!/usr/bin/env python3
import sys
from pathlib import Path
from playwright.sync_api import sync_playwright

root = Path(__file__).resolve().parents[1]
css = (root / 'public/assets/css/app.css').read_text(encoding='utf-8')
out_dir = Path('/tmp/takimbul-v106-club-browser-smoke')
out_dir.mkdir(parents=True, exist_ok=True)
viewports = [(320,760),(360,800),(375,812),(390,844),(414,896),(430,932),(768,1024),(1024,900),(1366,900),(1440,1000),(1920,1080)]

cards=[]
names=[
    'Murat Dinçer','Aleksandr Çok Uzun Soyadlı Futbolcu','Emirhan Yılmaz','Kerem Can',
    'Batuhan Aydın','Efe Demir','Arda Kılıç','Mehmet Ali Özkan','Can Berk','Deniz Aksoy','Umut Şahin','Oğuzhan Kaya'
]
for i,name in enumerate(names,1):
    score = 60 + (i % 28)
    cards.append(f'''<a class="tb-squad-card" style="--team-accent:#1f8f4e" href="#p{i}">
      <i class="tb-squad-card-accent"></i>
      <div class="tb-squad-card-top"><div class="tb-squad-rating"><strong>{score}</strong><small>TakımBul Form</small></div><div class="tb-squad-meta"><b>#{i}</b><span>{'Kanat' if i%2 else 'Orta Saha'}</span></div></div>
      <div class="tb-squad-avatar"><span>{name[0]}</span></div>
      <div class="tb-squad-name"><strong>{name}</strong><small>{'İlk Kadro' if i<8 else 'Yedek'}</small></div>
      <div class="tb-squad-badges"><span>{'Kaptan' if i==1 else 'Oyuncu'}</span><span>{'starter' if i<8 else 'substitute'}</span></div>
      <div class="tb-squad-numbers"><div><span>MAÇ</span><b>{i+6}</b></div><div><span>GOL</span><b>{i%6}</b></div><div><span>ASİST</span><b>{i%4}</b></div></div>
    </a>''')

slots=[
    ('GK',50,88,'Murat Dinçer'),('LB',20,64,'Aleksandr Çok Uzun Soyadlı Futbolcu'),('RB',80,64,'Emirhan Yılmaz'),
    ('CM1',36,40,'Kerem Can'),('CM2',64,40,'Batuhan Aydın'),('ST',50,17,'Efe Demir')
]
pitch=''.join(f'<div class="tb-pitch-slot" style="left:{x}%;top:{y}%"><span>{label}</span><a href="#"><b>#{n%11+1} {name}</b></a></div>' for n,(label,x,y,name) in enumerate(slots))

editors=''.join(f'''<article class="panel tb-member-editor"><header><span class="avatar-mini">{name[0]}</span><div><strong>{name}</strong><small>Global mevki</small></div></header><form><label>Forma No<input value="{i}"></label><label>Takım mevkii<select><option>Kanat</option></select></label><label>Kadro statüsü<select><option>İlk Kadro</option></select></label><button class="btn btn-primary">Oyuncu Ayarlarını Kaydet</button></form></article>''' for i,name in enumerate(names[:6],1))

html=f'''<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body class="v9-body">
<main class="container section-space tb-team-page" style="--team-accent:#1f8f4e">
<section class="tb-club-identity panel"><div class="tb-club-mark"><span>VF</span></div><div class="tb-club-title"><span class="eyebrow">KULÜP PROFİLİ</span><h1>Veribayt Futbol Kulübü Çok Uzun Takım Adı</h1><p>Alanya, Antalya · 2026 · Büyük Tesis Sahası</p><small>VRB</small></div><dl class="tb-club-facts"><div><dt>Kadro</dt><dd>18</dd></div><div><dt>Kaptan</dt><dd>Çok Uzun İsimli Kaptan</dd></div><div><dt>Maç</dt><dd>24</dd></div></dl><div class="tb-club-actions"><a class="btn btn-primary">Takımı Yönet</a></div></section>
<nav class="tb-club-tabs"><a>Genel Bakış</a><a>Kadro</a><a>Diziliş</a><a>Maçlar</a><a>İstatistik</a><a>Transferler</a></nav>
<section class="tb-team-section"><div class="tb-section-heading"><div><span class="eyebrow">KADRO</span><h2>Takım oyuncuları</h2></div><small>18 aktif oyuncu</small></div><div class="tb-squad-grid">{''.join(cards)}</div></section>
<section class="tb-team-section"><div class="tb-section-heading"><div><span class="eyebrow">DİZİLİŞ</span><h2>Takımın sahadaki yerleşimi</h2></div></div><div class="tb-formation-pitch">{pitch}</div></section>
<section class="tb-team-section"><div class="tb-stats-board panel"><div><strong>24</strong><span>Maç</span></div><div><strong>16</strong><span>Galibiyet</span></div><div><strong>81</strong><span>Atılan Gol</span></div><div><strong>42</strong><span>Yenilen Gol</span></div></div></section>
</main>
<main class="container section-space tb-team-manage"><section class="panel tb-manage-head"><div><span class="eyebrow">TAKIM YÖNETİMİ</span><h1>Veribayt Futbol Kulübü Çok Uzun Takım Adı</h1><p>Kulüp kimliği ve kadro yönetimi.</p></div><a class="btn btn-secondary">Takım Profiline Dön</a></section><section class="tb-manage-section"><div class="tb-manage-roster">{editors}</div></section></main>
</body></html>'''

failures=[]
with sync_playwright() as p:
    browser=p.chromium.launch(headless=True, executable_path='/usr/bin/chromium', args=['--no-sandbox','--disable-background-networking'])
    for width,height in viewports:
        ctx=browser.new_context(viewport={'width':width,'height':height},device_scale_factor=1)
        page=ctx.new_page()
        errors=[]
        page.on('pageerror', lambda exc,bucket=errors: bucket.append(str(exc)))
        try:
            page.set_content(html,wait_until='domcontentloaded')
            page.add_style_tag(content=css)
            page.wait_for_timeout(120)
            overflow=page.evaluate('''() => ({sw:document.documentElement.scrollWidth,cw:document.documentElement.clientWidth,bsw:document.body.scrollWidth})''')
            if max(overflow['sw'],overflow['bsw']) > overflow['cw']+1:
                failures.append(f'{width}px: horizontal overflow {overflow}')
            cards_loc=page.locator('.tb-squad-card')
            if cards_loc.count()!=len(names):
                failures.append(f'{width}px: squad cards missing')
            # Cards must have a usable, non-collapsed box and not overlap the next card.
            boxes=[cards_loc.nth(i).bounding_box() for i in range(cards_loc.count())]
            for i,b in enumerate(boxes):
                if not b or b['width'] < 120 or b['height'] < 180:
                    failures.append(f'{width}px: card {i} collapsed {b}')
            for i,a in enumerate(boxes):
                if not a: continue
                for j,b in enumerate(boxes[i+1:],i+1):
                    if not b: continue
                    overlap_x=min(a['x']+a['width'],b['x']+b['width'])-max(a['x'],b['x'])
                    overlap_y=min(a['y']+a['height'],b['y']+b['height'])-max(a['y'],b['y'])
                    if overlap_x>1 and overlap_y>1:
                        failures.append(f'{width}px: squad cards {i}/{j} overlap')
                        break
            pitch_loc=page.locator('.tb-formation-pitch').first
            pb=pitch_loc.bounding_box()
            if not pb or pb['height'] < 220:
                failures.append(f'{width}px: formation pitch collapsed {pb}')
            else:
                for i in range(page.locator('.tb-pitch-slot').count()):
                    sb=page.locator('.tb-pitch-slot').nth(i).bounding_box()
                    if not sb: continue
                    # Slots may visually extend a few pixels but must remain materially inside the pitch.
                    if sb['x'] < pb['x']-3 or sb['x']+sb['width'] > pb['x']+pb['width']+3 or sb['y'] < pb['y']-3 or sb['y']+sb['height'] > pb['y']+pb['height']+3:
                        failures.append(f'{width}px: pitch slot {i} escapes pitch')
            if errors:
                failures.append(f'{width}px: page errors: '+ ' | '.join(errors[:3]))
            page.screenshot(path=str(out_dir/f'club-{width}.png'),full_page=True)
        except Exception as exc:
            failures.append(f'{width}px: browser exception: {exc}')
        finally:
            page.close();ctx.close()
    browser.close()

if failures:
    print('FAIL v106_club_browser_smoke')
    for failure in failures: print('- '+failure)
    sys.exit(1)
print(f'PASS v106_club_browser_smoke ({len(viewports)} responsive renders, screenshots: {out_dir})')
