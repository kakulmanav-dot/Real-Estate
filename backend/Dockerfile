# syntax=docker/dockerfile:1

FROM php:8.2-cli-bookworm

# System packages needed to build the PHP extensions Laravel requires.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        unzip \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pdo_mysql \
        bcmath \
        pcntl \
        zip \
        gd \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

# Composer, copied from its official image rather than installed via script.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source (see .dockerignore for what is excluded).
COPY . .

# Production dependencies only; no dev tooling, no interactive prompts.
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# storage/ and bootstrap/cache/ must be writable by the app at runtime
# (logs, framework cache, uploaded files on the ephemeral local disk).
RUN chmod -R 775 storage bootstrap/cache

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Render sets $PORT at runtime; 10000 is only the documented fallback.
EXPOSE 10000

CMD ["/usr/local/bin/start.sh"]
