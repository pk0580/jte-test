FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash \
    icu-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    zlib-dev \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install \
    intl \
    pdo_mysql \
    soap \
    zip \
    bcmath \
    mbstring

# Install Redis extension (using pecl or fallback to manual)
RUN pecl update-channels && pecl install --force redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
