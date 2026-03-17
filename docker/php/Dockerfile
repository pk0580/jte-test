FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash \
    icu-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    zlib-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install \
    intl \
    pdo_mysql \
    soap \
    zip \
    bcmath \
    mbstring

# Install Redis and Xdebug extensions
RUN pecl update-channels && \
    pecl install --force redis xdebug && \
    docker-php-ext-enable redis xdebug

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
