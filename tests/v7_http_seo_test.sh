#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
PORT="${PORT:-8096}"
php -S "127.0.0.1:${PORT}" -t public public/index.php >"/tmp/takimbul-v7-http-${PORT}.log" 2>&1 &
pid=$!
trap 'kill "$pid" 2>/dev/null || true' EXIT
sleep 1
base="http://127.0.0.1:${PORT}"
home_headers="$(curl -sS -D - "$base/" -o /tmp/takimbul-v7-home.html)"
grep -q 'HTTP/1.1 200' <<<"$home_headers"
grep -qi 'Cache-Control: public' <<<"$home_headers"
if grep -qi 'Set-Cookie:' <<<"$home_headers"; then echo 'FAIL anonymous home should not create a session cookie'; exit 1; fi
grep -q 'rel="canonical" href="https://takimbul.com.tr/"' /tmp/takimbul-v7-home.html
grep -q 'application/ld+json' /tmp/takimbul-v7-home.html
grep -q 'name="robots" content="index,follow"' /tmp/takimbul-v7-home.html
robots="$(curl -sS "$base/robots.txt")"
grep -q 'Sitemap: https://takimbul.com.tr/sitemap.xml' <<<"$robots"
sitemap="$(curl -sS "$base/sitemap.xml")"
grep -q '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' <<<"$sitemap"
status="$(curl -sS -o /tmp/takimbul-v7-404.html -w '%{http_code}' "$base/boyle-bir-sayfa-yok")"
[[ "$status" == '404' ]]
grep -q 'name="robots" content="noindex,nofollow"' /tmp/takimbul-v7-404.html
login_headers="$(curl -sS -D - "$base/giris" -o /dev/null)"
grep -qi 'Set-Cookie: takimbul_session=' <<<"$login_headers"
grep -qi 'HttpOnly' <<<"$login_headers"
grep -qi 'SameSite=Lax' <<<"$login_headers"
echo 'PASS v7_http_seo_test'
