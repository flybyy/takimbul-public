#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
PORT="${TAKIMBUL_V103_BROWSER_PORT:-8133}"
BASE="http://127.0.0.1:${PORT}"
LOG="${TMPDIR:-/tmp}/takimbul-v103-browser-${PORT}.log"
php -S "127.0.0.1:${PORT}" -t public public/index.php >"$LOG" 2>&1 &
pid=$!
trap 'kill "$pid" 2>/dev/null || true' EXIT
sleep 0.7
TAKIMBUL_BASE_URL="$BASE" python3 tests/v103_browser_smoke.py
