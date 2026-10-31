FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    TZ=Europe/Istanbul

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring gd opcache \
    && a2enmod rewrite headers expires deflate \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/app.docker.php /var/www/html/config/app.php
COPY docker/php.ini /usr/local/etc/php/conf.d/99-takimbul.ini
COPY docker/entrypoint.sh /usr/local/bin/takimbul-entrypoint

RUN chmod +x /usr/local/bin/takimbul-entrypoint /var/www/html/scripts/docker-smoke.sh \
    && mkdir -p /var/www/html/storage/logs /var/www/html/storage/cache /var/www/html/public/uploads/avatars \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/public/uploads

EXPOSE 80

HEALTHCHECK --interval=20s --timeout=5s --start-period=40s --retries=5 \
    CMD php /var/www/html/docker/healthcheck.php || exit 1

ENTRYPOINT ["/usr/local/bin/takimbul-entrypoint"]
CMD ["apache2-foreground"]
