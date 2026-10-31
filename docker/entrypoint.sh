#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p storage/logs storage/cache public/uploads/avatars
chown -R www-data:www-data storage public/uploads

printf '%s\n' '[TakımBul] Veritabanı bekleniyor...'
attempt=0
until php -r 'require "/var/www/html/app/Core/Database.php"; App\Core\Database::connection()->query("SELECT 1");' >/dev/null 2>&1; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 60 ]; then
        printf '%s\n' '[TakımBul] Veritabanına bağlanılamadı.' >&2
        exit 1
    fi
    sleep 2
done

printf '%s\n' '[TakımBul] Şema kontrolü / güvenli onarım...'
php scripts/production-repair.php --apply

if [ -n "${TAKIMBUL_ADMIN_PASSWORD:-}" ]; then
    printf '%s\n' '[TakımBul] Yönetici hesabı hazırlanıyor...'
    php scripts/admin-bootstrap.php
else
    printf '%s\n' '[TakımBul] TAKIMBUL_ADMIN_PASSWORD boş; admin bootstrap atlandı.'
fi

if [ "$#" -eq 0 ]; then
    set -- apache2-foreground
fi

exec "$@"
