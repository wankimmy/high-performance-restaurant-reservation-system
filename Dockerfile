# =============================================================================
# Restaurant Reservation System - Application Container
# Includes: PHP 8.4-FPM, Nginx, Queue Workers, Scheduler
# =============================================================================
FROM php:8.4-fpm

# Set working directory
WORKDIR /var/www/html

# =============================================================================
# Install System Dependencies
# =============================================================================
RUN apt-get update && apt-get install -y \
    # Version control
    git \
    curl \
    # Image processing libraries
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    # Compression
    zip \
    unzip \
    libzip-dev \
    # Brotli compression (for Swoole)
    libbrotli-dev \
    # Database drivers
    libpq-dev \
    # Internationalization
    libicu-dev \
    # Process management
    supervisor \
    # Web server
    nginx \
    # Node.js for Vite asset building
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    # Install PHP extensions
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    # Clean up to reduce image size
    && rm -rf /var/lib/apt/lists/*

# =============================================================================
# Install PHP Extensions
# =============================================================================
# Redis extension for caching and queue
RUN pecl install redis && docker-php-ext-enable redis

# Swoole extension for Laravel Octane
# Install non-interactively: disable sockets (no), enable brotli (yes), disable others (no)
RUN printf "no\nyes\nno\nno\nno\nno\nno\nno\nno\nno\nno\nno\nno\n" | pecl install swoole && docker-php-ext-enable swoole

# =============================================================================
# Install Composer (PHP package manager)
# =============================================================================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# =============================================================================
# Copy Configuration Files
# =============================================================================
# Nginx - Web server configuration
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

# PHP - Runtime configuration (memory limits, upload sizes, etc.)
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# Supervisor - Process manager (manages Nginx, PHP-FPM, Queue, Cron)
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# =============================================================================
# Copy Application Code
# =============================================================================
COPY . /var/www/html

# =============================================================================
# Copy and Prepare Startup Scripts
# =============================================================================
COPY docker/scripts/startup.sh /usr/local/bin/startup.sh
COPY docker/scripts/fix-permissions.sh /usr/local/bin/fix-permissions.sh
COPY docker/scripts/configure-supervisor.sh /usr/local/bin/configure-supervisor.sh
RUN chmod +x /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/fix-permissions.sh
RUN chmod +x /usr/local/bin/configure-supervisor.sh

# =============================================================================
# Set Permissions & Create Required Directories
# =============================================================================
# Create Laravel required directories first (create individually to avoid brace expansion issues)
RUN mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/cache/data \
    && mkdir -p /var/www/html/storage/framework/testing \
    && mkdir -p /var/www/html/storage/app/public \
    && mkdir -p /var/www/html/storage/app/private \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache \
    # Set ownership to www-data
    && chown -R www-data:www-data /var/www/html \
    # Set proper permissions (775 for directories, 664 for files)
    && find /var/www/html/storage -type d -exec chmod 775 {} \; \
    && find /var/www/html/storage -type f -exec chmod 664 {} \; \
    && find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \; \
    && find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \; \
    # Ensure critical directories are writable
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    # Set sticky bit on storage directories so new files inherit group ownership
    && chmod g+s /var/www/html/storage \
    && chmod g+s /var/www/html/storage/framework \
    && chmod g+s /var/www/html/storage/framework/views \
    && chmod g+s /var/www/html/storage/framework/cache \
    && chmod g+s /var/www/html/storage/framework/sessions \
    && chmod g+s /var/www/html/storage/logs \
    && chmod g+s /var/www/html/bootstrap/cache \
    # Create system directories
    && mkdir -p /var/log/supervisor \
    && mkdir -p /run/php

# =============================================================================
# Expose HTTP Port
# =============================================================================
EXPOSE 80

# =============================================================================
# Container Entry Point
# Runs startup script which:
# - Installs dependencies
# - Generates app key
# - Runs migrations
# - Seeds database
# - Starts all services via Supervisor
# =============================================================================
CMD ["/usr/local/bin/startup.sh"]
