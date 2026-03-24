FROM php:8.3-fpm

# System dependencies για SQLite
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# EntryPoint
COPY docker/php/entrypoint.sh /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
