#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
PORT="${TAKIMBUL_V104_HTTP_PORT:-8134}"
BASE="http://127.0.0.1:${PORT}"
LOG="${TMPDIR:-/tmp}/takimbul-v104-http-${PORT}.log"
php -S "127.0.0.1:${PORT}" -t public public/index.php >"$LOG" 2>&1 &
pid=$!
trap 'kill "$pid" 2>/dev/null || true' EXIT
sleep 0.6

assert_page() {
  local path="$1" expected="$2"
  local out code
  out="$(mktemp)"
  code="$(curl -sS -o "$out" -w '%{http_code}' "$BASE$path")"
  if [[ "$code" != "200" ]]; then
    echo "FAIL $path HTTP $code" >&2
    cat "$LOG" >&2 || true
    rm -f "$out"
    exit 1
  fi
  grep -Fq "$expected" "$out" || { echo "FAIL $path missing: $expected" >&2; rm -f "$out"; exit 1; }
  rm -f "$out"
}

assert_page "/haberler" "Haberler"
assert_page "/oyuncu-bul" "data-location-province"
assert_page "/uye-ol" 'name="password_confirmation"'
assert_page "/uye-ol" 'data-location-district'

code="$(curl -sS -o /tmp/takimbul-v104-favicon.svg -w '%{http_code}' "$BASE/favicon.svg")"
[[ "$code" == "200" ]] || { echo "FAIL favicon HTTP $code" >&2; exit 1; }
grep -Fq '<svg' /tmp/takimbul-v104-favicon.svg || { echo "FAIL favicon body" >&2; exit 1; }
rm -f /tmp/takimbul-v104-favicon.svg

curl -sS "$BASE/assets/data/turkey-locations.json" | python3 -c 'import json,sys; d=json.load(sys.stdin); assert len(d)==81; assert sum(len(v) for v in d.values())==973; assert "Alanya" in d["Antalya"]'

echo "PASS v104_http_smoke"
