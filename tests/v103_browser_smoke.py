#!/usr/bin/env python3
import os
import re
import sys
import urllib.error
import urllib.request
from pathlib import Path
from playwright.sync_api import sync_playwright

root = Path(__file__).resolve().parents[1]
base = os.getenv('TAKIMBUL_BASE_URL', 'http://127.0.0.1:8093').rstrip('/')
out_dir = Path(os.getenv('TAKIMBUL_V103_SCREENSHOT_DIR', '/tmp/takimbul-v103-browser-smoke'))
out_dir.mkdir(parents=True, exist_ok=True)
css = (root / 'public/assets/css/app.css').read_text(encoding='utf-8')
js = (root / 'public/assets/js/app.js').read_text(encoding='utf-8')
location_js = (root / 'public/assets/js/location-selects.js').read_text(encoding='utf-8')
location_data = (root / 'public/assets/data/turkey-locations.json').read_text(encoding='utf-8')
viewports = [(320, 760), (360, 800), (375, 812), (390, 844), (414, 896), (430, 932), (768, 1024), (1024, 900), (1366, 900), (1440, 1000), (1920, 1080)]
routes = [
    ('home', '/', 200, 'Maçını kur.'),
    ('register', '/uye-ol', 200, 'Futbol ağındaki yerini al.'),
    ('login', '/giris', 200, 'Maça geri dön.'),
    ('news', '/haberler', 200, 'Sahadan ve amatör futboldan haberler.'),
    ('players', '/oyuncu-bul', 200, 'Takımının eksiğini bul.'),
    ('404', '/v103-garantili-olmayan-sayfa', 404, 'Bu sayfa sahada değil.'),
]
failures = []

def fetch(path: str):
    request = urllib.request.Request(base + path, headers={'User-Agent': 'TakimBul-V103-QA/1.0'})
    try:
        with urllib.request.urlopen(request, timeout=10) as response:
            return response.status, response.read().decode('utf-8', errors='replace')
    except urllib.error.HTTPError as exc:
        return exc.code, exc.read().decode('utf-8', errors='replace')


def prepare_html(html: str) -> str:
    html = re.sub(r'<link[^>]+href="/assets/css/app\.css[^>]*>', '', html, flags=re.I)
    html = re.sub(r'<script[^>]+src="/assets/js/app\.js[^>]*></script>', '', html, flags=re.I)
    html = re.sub(r'<script[^>]+src="/assets/js/location-selects\.js[^>]*></script>', '', html, flags=re.I)
    return html

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path='/usr/bin/chromium', args=['--no-sandbox', '--disable-background-networking'])
    for width, height in viewports:
        context = browser.new_context(viewport={'width': width, 'height': height}, device_scale_factor=1)
        for name, path, expected_status, expected_text in routes:
            page = context.new_page()
            page_errors = []
            page.on('pageerror', lambda exc, bucket=page_errors: bucket.append(str(exc)))
            try:
                status, html = fetch(path)
                if status != expected_status:
                    failures.append(f'{width}px {path}: HTTP {status}, expected {expected_status}')
                page.set_content(prepare_html(html), wait_until='domcontentloaded', timeout=10000)
                page.add_style_tag(content=css)
                page.add_script_tag(content='window.TAKIMBUL_LOCATION_DATA = ' + location_data + ';')
                page.add_script_tag(content=location_js)
                page.add_script_tag(content=js)
                body_text = page.locator('body').inner_text(timeout=5000)
                if expected_text not in body_text:
                    failures.append(f'{width}px {path}: expected text missing: {expected_text}')
                if expected_status == 200 and 'Beklenmeyen bir sorun oluştu.' in body_text:
                    failures.append(f'{width}px {path}: generic 500 surface rendered')
                overflow = page.evaluate('''() => ({sw: document.documentElement.scrollWidth, cw: document.documentElement.clientWidth, bsw: document.body.scrollWidth})''')
                if max(overflow['sw'], overflow['bsw']) > overflow['cw'] + 1:
                    failures.append(f"{width}px {path}: horizontal overflow sw={overflow['sw']} body={overflow['bsw']} cw={overflow['cw']}")
                if page_errors:
                    failures.append(f'{width}px {path}: page errors: ' + ' | '.join(page_errors[:3]))
                if width >= 1000:
                    nav_last = page.locator('.tb-main-nav > .tb-nav-direct').last
                    search = page.locator('.v10-header-search')
                    actions = page.locator('.v9-header-actions')
                    if nav_last.count() == 1 and search.count() == 1 and actions.count() == 1 and search.is_visible():
                        nb, sb, ab = nav_last.bounding_box(), search.bounding_box(), actions.bounding_box()
                        if nb and sb and nb['x'] + nb['width'] > sb['x'] - 3:
                            failures.append(f"{width}px {path}: desktop nav overlaps header search")
                        if sb and ab and sb['x'] + sb['width'] > ab['x'] - 3:
                            failures.append(f"{width}px {path}: header search overlaps actions")
                if name == 'players':
                    if page.locator('select[data-location-province]').count() != 1 or page.locator('select[data-location-district]').count() != 1:
                        failures.append(f'{width}px players: province/district linked selects missing')
                if name in ('register', 'login') and width <= 430:
                    mobile_nav = page.locator('.mobile-bottom-nav')
                    if mobile_nav.count() and mobile_nav.is_visible():
                        failures.append(f'{width}px {name}: auth page must not be covered by mobile bottom navigation')
                if name == 'register' and width == 360:
                    toggle = page.locator('[data-password-toggle]').first
                    if toggle.count() != 1:
                        failures.append('360px register: password toggle missing')
                    else:
                        toggle.click()
                        if page.locator('#register-password').get_attribute('type') != 'text':
                            failures.append('360px register: password toggle did not reveal password')
                    province = page.locator('[data-location-province]').first
                    district = page.locator('[data-location-district]').first
                    if province.count() != 1 or district.count() != 1:
                        failures.append('360px register: linked province/district selects missing')
                    else:
                        province.select_option('Antalya')
                        page.wait_for_timeout(50)
                        options = district.locator('option').all_text_contents()
                        if 'Alanya' not in options:
                            failures.append('360px register: Antalya selection did not populate Alanya')
                page.screenshot(path=str(out_dir / f'{name}-{width}.png'), full_page=True)
            except Exception as exc:
                failures.append(f'{width}px {path}: browser exception: {exc}')
            finally:
                page.close()
        context.close()
    browser.close()

if failures:
    print('FAIL v103_browser_smoke')
    for failure in failures:
        print('- ' + failure)
    sys.exit(1)
print(f'PASS v103_browser_smoke ({len(viewports) * len(routes)} renders, screenshots: {out_dir})')
