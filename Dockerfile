FROM php:8.4-cli-alpine

# Cài đúng những thứ cần thiết nhất
RUN apk add --no-cache \
    ca-certificates \
    curl \
    openssl \
    libpq \
    icu-libs \
    oniguruma \
    libzip

# Update cert
RUN update-ca-certificates

# Build PHP extensions (tối giản)
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    postgresql-dev \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        mbstring \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

WORKDIR /var/www

EXPOSE 8000

CMD ["sh", "-c", "php artisan storage:link --quiet 2>/dev/null || true && php artisan serve --host=0.0.0.0 --port=8000"]
