FROM php:8.3-cli

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip ca-certificates \
      libzip-dev zlib1g-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* /app/
RUN composer install --no-interaction --no-progress

COPY public /app/public
COPY src /app/src

EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public", "public/index.php"]
