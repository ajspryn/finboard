# ─── Stage 1: Build dependencies ─────────────────────────────────────────────
FROM php:8.2-fpm AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    default-mysql-client \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        gd \
        zip \
        bcmath \
        pcntl \
        intl \
        xml \
        opcache

# PHP OPcache configuration for production
RUN echo "opcache.enable=1\n\
opcache.memory_consumption=256\n\
opcache.interned_strings_buffer=16\n\
opcache.max_accelerated_files=20000\n\
opcache.revalidate_freq=0\n\
opcache.validate_timestamps=0\n\
opcache.fast_shutdown=1" > /usr/local/etc/php/conf.d/opcache.ini

# PHP production config
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && echo "upload_max_filesize=100M\n\
post_max_size=100M\n\
memory_limit=256M\n\
max_execution_time=300" >> /usr/local/etc/php/php.ini

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ─── Stage 2: Install PHP dependencies ───────────────────────────────────────
FROM base AS vendor

COPY composer.json composer.lock ./

RUN composer install \
    --optimize-autoloader \
    --no-dev \
    --no-interaction \
    --no-scripts

# ─── Stage 3: Final image ────────────────────────────────────────────────────
FROM base AS final

WORKDIR /var/www/html

# Copy application code
COPY . .

# Copy Composer vendor from vendor stage
COPY --from=vendor /var/www/html/vendor ./vendor

# Run composer scripts (post-autoload-dump)
RUN composer dump-autoload --optimize --no-dev

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy Nginx configuration
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default \
    && rm -f /etc/nginx/sites-enabled/000-default* 2>/dev/null || true

# Copy Supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy and prepare entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Create log directories
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /var/log/nginx

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
