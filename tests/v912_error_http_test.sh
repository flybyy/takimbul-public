#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
PORT="${TAKIMBUL_V912_ERROR_PORT:-8112}"
php -S "127.0.0.1:${PORT}" -t public public/index.php >"/tmp/takimbul-v912-error-${PORT}.log" 2>&1 &
pid=$!
trap 'kill "$pid" 2>/dev/null || true' EXIT
sleep 0.5

status404="$(curl -sS -o /tmp/takimbul-v912-404.html -w '%{http_code}' "http://127.0.0.1:${PORT}/bu-sayfa-yok-v912")"
[[ "$status404" == "404" ]]
grep -q 'Bu sayfa sahada değil' /tmp/takimbul-v912-404.html
grep -q '/assets/css/app-v108.css' /tmp/takimbul-v912-404.html

status419="$(curl -sS -o /tmp/takimbul-v912-419.html -w '%{http_code}' -X POST "http://127.0.0.1:${PORT}/cikis")"
[[ "$status419" == "419" ]]
grep -q 'Formun süresi doldu' /tmp/takimbul-v912-419.html
grep -q '/assets/css/app-v108.css' /tmp/takimbul-v912-419.html

if grep -q 'Beklenmeyen bir sorun oluştu' /tmp/takimbul-v912-419.html; then
  echo 'FAIL CSRF error rendered generic 500 screen'
  exit 1
fi

echo 'PASS v912_error_http_test'
