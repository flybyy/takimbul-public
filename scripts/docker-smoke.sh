#!/usr/bin/env sh
set -eu

BASE_URL="${1:-http://localhost:${APP_PORT:-8080}}"

printf '%s\n' "[TakımBul] Docker smoke: ${BASE_URL}"
curl -fsS "${BASE_URL}/" >/dev/null
curl -fsS "${BASE_URL}/robots.txt" >/dev/null
curl -fsS "${BASE_URL}/giris" >/dev/null
curl -fsS "${BASE_URL}/uye-ol" >/dev/null

docker compose exec -T app php /var/www/html/docker/healthcheck.php
printf '%s\n' '[TakımBul] Docker smoke PASS'
