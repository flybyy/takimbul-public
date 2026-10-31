#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
PORT="${TAKIMBUL_V911_TEST_PORT:-8108}"
SID="v911loggedin123456789"
SID="$SID" php -r 'session_name("takimbul_session"); session_id(getenv("SID")); session_start(); $_SESSION["user"]=["id"=>42,"name"=>"Test Oyuncu","email"=>"test@example.com","role"=>"player"]; $_SESSION["_auth_checked_at"]=time(); $_SESSION["_activity_touched_at"]=time(); $_SESSION["_session_regenerated_at"]=time(); session_write_close();'
php -S "127.0.0.1:${PORT}" -t public public/index.php >"/tmp/takimbul-v911-http-${PORT}.log" 2>&1 &
pid=$!
trap 'kill "$pid" 2>/dev/null || true' EXIT
sleep 0.5
status="$(curl -sS -o /tmp/takimbul-v911-logged-home.html -w '%{http_code}' -H "Cookie: takimbul_session=${SID}" "http://127.0.0.1:${PORT}/")"
[[ "$status" == "200" ]]
grep -q 'Test Oyuncu' /tmp/takimbul-v911-logged-home.html
if grep -q 'Beklenmeyen bir sorun oluştu' /tmp/takimbul-v911-logged-home.html; then
  echo 'FAIL logged-in home rendered generic error screen'
  exit 1
fi
if grep -q '>Yönetim<' /tmp/takimbul-v911-logged-home.html; then
  echo 'FAIL public player session exposed admin link'
  exit 1
fi
echo 'PASS v911_logged_in_http_test'
