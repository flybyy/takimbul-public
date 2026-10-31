#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
PORT="${TAKIMBUL_V102_TEST_PORT:-8124}"
SID="v102showcase123456789"
SID="$SID" php -r 'session_name("takimbul_session"); session_id(getenv("SID")); session_start(); $_SESSION["user"]=["id"=>77,"name"=>"Vitrin Oyuncu","email"=>"vitrin@example.com","role"=>"player"]; $_SESSION["_auth_checked_at"]=time(); $_SESSION["_activity_touched_at"]=time(); $_SESSION["_session_regenerated_at"]=time(); session_write_close();'
php -S "127.0.0.1:${PORT}" -t public public/index.php >"/tmp/takimbul-v102-${PORT}.log" 2>&1 &
pid=$!
trap 'kill "$pid" 2>/dev/null || true' EXIT
sleep 0.5

status="$(curl -sS -o /tmp/takimbul-v102-market.html -w '%{http_code}' "http://127.0.0.1:${PORT}/amator-futbolcular")"
[[ "$status" == "200" ]]
grep -q 'AMATÖR FUTBOLCU VİTRİNİ' /tmp/takimbul-v102-market.html
grep -q 'name="profile_intent"' /tmp/takimbul-v102-market.html
grep -q 'Oyuncu Profilini Oluştur' /tmp/takimbul-v102-market.html

status="$(curl -sS -o /tmp/takimbul-v102-editor.html -w '%{http_code}' -H "Cookie: takimbul_session=${SID}" "http://127.0.0.1:${PORT}/amator-ilanim")"
[[ "$status" == "200" ]]
grep -q 'Kendimi tanıtıyorum' /tmp/takimbul-v102-editor.html
grep -q 'name="profile_intent" value="both" checked' /tmp/takimbul-v102-editor.html
grep -q 'Profilimi İncelemeye Gönder' /tmp/takimbul-v102-editor.html

echo 'PASS v102_player_showcase_http_test'
