FROM php:8.4-fpm

WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libmagickwand-dev \
    zip unzip libzip-dev libbrotli-dev libpq-dev libicu-dev \
    supervisor lsof procps \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && pecl install imagick redis \
    && docker-php-ext-enable imagick redis \
    && printf "no\nyes\nno\nno\nno\nno\nno\nno\nno\nno\nno\nno\nno\n" | pecl install swoole \
    && docker-php-ext-enable swoole \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy configuration files
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy application code
COPY . /var/www/html

# Create directories and set permissions
RUN mkdir -p storage/framework/{sessions,views,cache,testing} \
         storage/framework/cache/data storage/app/{public,private} \
         storage/logs bootstrap/cache /var/log/supervisor /run/php \
    && chown -R www-data:www-data /var/www/html \
    && find storage bootstrap/cache -type d -exec chmod 775 {} \; \
    && find storage bootstrap/cache -type f -exec chmod 664 {} \; \
    && find storage bootstrap/cache -type d -exec chmod g+s {} \;

# Install Composer dependencies
RUN if [ -f "composer.json" ]; then \
    composer install --optimize-autoloader --no-interaction --no-dev || \
    composer install --optimize-autoloader --no-interaction; \
    fi

# Install Node dependencies and build assets
RUN if [ -f "package.json" ]; then \
    npm install --silent && npm run build 2>/dev/null || true; \
    fi

# Install Laravel Octane
RUN if [ -f "artisan" ]; then \
    php artisan octane:install --server=swoole --no-interaction 2>/dev/null || true; \
    fi

# Create entrypoint script
RUN printf '#!/bin/bash\n\
set -e\n\
cd /var/www/html\n\
\n\
# Fix permissions\n\
chown -R www-data:www-data storage bootstrap/cache\n\
chmod -R 775 storage bootstrap/cache\n\
find storage bootstrap/cache -type d -exec chmod g+s {} \\;\n\
\n\
# Clean up any orphaned Octane processes on port 80\n\
if command -v lsof >/dev/null 2>&1; then\n\
    lsof -ti:80 | xargs -r kill -9 2>/dev/null || true\n\
elif command -v fuser >/dev/null 2>&1; then\n\
    fuser -k 80/tcp 2>/dev/null || true\n\
fi\n\
\n\
# Ensure .env exists\n\
if [ ! -f ".env" ] && [ -f ".env.example" ]; then\n\
    cp .env.example .env\n\
fi\n\
\n\
# Validate and fix APP_KEY\n\
if [ -f ".env" ]; then\n\
    # Ensure .env is writable\n\
    chmod 664 .env 2>/dev/null || true\n\
    \n\
    # Check if APP_KEY exists and is valid (single base64: prefix, not empty)\n\
    APP_KEY_LINE=$(grep "^APP_KEY=" .env 2>/dev/null || echo "")\n\
    if [ -z "$APP_KEY_LINE" ] || echo "$APP_KEY_LINE" | grep -q "^APP_KEY=$" || echo "$APP_KEY_LINE" | grep -q "base64:.*base64:" || ! echo "$APP_KEY_LINE" | grep -q "^APP_KEY=base64:"; then\n\
        echo "⚠️  APP_KEY missing or invalid. Generating new key..."\n\
        # Remove any existing malformed APP_KEY line\n\
        sed -i "/^APP_KEY=/d" .env 2>/dev/null || true\n\
        # Generate new key\n\
        php artisan key:generate --force\n\
        # Clear all caches to ensure new key is loaded\n\
        php artisan config:clear 2>/dev/null || true\n\
        php artisan cache:clear 2>/dev/null || true\n\
        # Verify the key was set\n\
        if grep -q "^APP_KEY=base64:" .env; then\n\
            echo "✅ APP_KEY generated successfully"\n\
            # Show first few chars for verification\n\
            APP_KEY_VALUE=$(grep "^APP_KEY=" .env | cut -d= -f2 | cut -c1-20)\n\
            echo "   Key starts with: ${APP_KEY_VALUE}..."\n\
        else\n\
            echo "❌ Failed to generate APP_KEY"\n\
        fi\n\
    else\n\
        echo "✅ APP_KEY is valid"\n\
        # Clear config cache to ensure .env is read fresh\n\
        php artisan config:clear 2>/dev/null || true\n\
        php artisan cache:clear 2>/dev/null || true\n\
    fi\n\
fi\n\
\n\
# Ensure Vite manifest exists (bind-mount can hide built assets from the image)\n\
if [ -f "package.json" ] && [ ! -f "public/build/manifest.json" ]; then\n\
    echo "📦 Vite manifest missing, building frontend assets..."\n\
    if [ ! -d "node_modules" ]; then\n\
        npm install --silent\n\
    fi\n\
    npm run build\n\
fi\n\
\n\
echo "✅ Starting supervisor..."\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf\n\
' > /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]
