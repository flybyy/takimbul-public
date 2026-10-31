#!/usr/bin/env bash
set -euo pipefail
PORT="${TAKIMBUL_TEST_PORT:-8099}"
LOG="${TMPDIR:-/tmp}/takimbul-http-test.log"
php -S "127.0.0.1:${PORT}" -t public public/index.php >"$LOG" 2>&1 &
PID=$!
trap 'kill "$PID" 2>/dev/null || true' EXIT
sleep 0.5
BODY="$(curl -fsS "http://127.0.0.1:${PORT}/")"
printf '%s' "$BODY" | grep -Fq 'Maçını kur.'
printf '%s' "$BODY" | grep -Fq 'home-network-pulse'
printf '%s' "$BODY" | grep -Fq 'brand-wordmark'
echo 'PASS http_smoke_test'
