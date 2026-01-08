#!/bin/bash

echo "🚀 Starting Restaurant Reservation System..."

cd /var/www/html

# Create required directories first
echo "📁 Creating required directories..."
mkdir -p storage/framework/{sessions,views,cache,testing}
mkdir -p storage/framework/cache/data
mkdir -p storage/app/{public,private}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set proper permissions permanently
echo "🔒 Setting permissions permanently..."
# Ensure entire application directory is owned by www-data
chown -R www-data:www-data /var/www/html

# Set permissions (775 for directories, 664 for files)
find /var/www/html/storage -type d -exec chmod 775 {} \;
find /var/www/html/storage -type f -exec chmod 664 {} \;
find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;
find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \;

# Ensure all storage and cache directories are writable
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Set sticky bit on storage directories to ensure new files inherit ownership
chmod g+s /var/www/html/storage
chmod g+s /var/www/html/storage/framework
chmod g+s /var/www/html/storage/framework/views
chmod g+s /var/www/html/storage/framework/cache
chmod g+s /var/www/html/storage/framework/sessions
chmod g+s /var/www/html/storage/logs
chmod g+s /var/www/html/bootstrap/cache

# Verify permissions
if [ -w "/var/www/html/storage/framework/views" ] && [ -O "/var/www/html/storage/framework/views" ] || [ -G "/var/www/html/storage/framework/views" ]; then
    echo "✅ Permissions set successfully (with sticky bit for persistent ownership)"
else
    echo "⚠️  Warning: storage/framework/views may not be writable"
fi

# Create .env from .env.example if it doesn't exist
if [ ! -f ".env" ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
fi

# Install composer dependencies if vendor doesn't exist
if [ ! -d "vendor" ]; then
    echo "📦 Installing Composer dependencies..."
    composer install --optimize-autoloader --no-interaction --no-dev || {
        echo "❌ Composer install failed, trying without --no-dev..."
        composer install --optimize-autoloader --no-interaction || exit 1
    }
fi

# Generate app key if not exists
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force || {
        echo "❌ Key generation failed"
        exit 1
    }
    # Fix ownership after key generation
    chown -R www-data:www-data /var/www/html
fi

# Wait for database to be ready
echo "⏳ Waiting for database..."
MAX_TRIES=30
TRIES=0
until php artisan db:show 2>/dev/null || [ $TRIES -eq $MAX_TRIES ]; do
    echo "Database not ready, waiting... ($TRIES/$MAX_TRIES)"
    sleep 2
    TRIES=$((TRIES + 1))
done

if [ $TRIES -eq $MAX_TRIES ]; then
    echo "❌ Database connection timeout"
    exit 1
fi

echo "✅ Database is ready!"

# Run fresh migrations (drops all tables and re-runs migrations)
echo "📊 Running fresh database migrations..."
php artisan migrate:fresh --force || {
    echo "❌ Migration failed"
    exit 1
}
# Fix ownership after migrations (sticky bit ensures new files inherit group)
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

# Seed database with fresh data
echo "🌱 Seeding database with fresh data..."
php artisan db:seed --force || {
    echo "❌ Seeding failed"
    exit 1
}
# Fix ownership after seeding
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

# Install and build Vite assets
if [ -f "package.json" ]; then
    echo "📦 Installing Node.js dependencies..."
    npm install --silent 2>/dev/null || echo "npm install skipped"
    
    echo "🔨 Building Vite assets..."
    npm run build 2>/dev/null || echo "Vite build skipped"
fi

# Cache configuration
echo "⚡ Optimizing application..."
# Run artisan commands (sticky bit ensures new files inherit group ownership)
php artisan config:cache 2>/dev/null || echo "Config cache skipped"
php artisan route:cache 2>/dev/null || echo "Route cache skipped"
# Fix ownership after caching
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

# Fix permissions again before caching views (sticky bit ensures new files inherit ownership)
echo "🔒 Ensuring permissions before view cache..."
# Fix ownership of all files in storage (sticky bit should prevent this, but just in case)
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

# Only cache views if directory exists and is writable
if [ -d "storage/framework/views" ] && [ -w "storage/framework/views" ]; then
    php artisan view:cache 2>/dev/null || echo "View cache skipped"
    # Fix ownership after view caching
    chown -R www-data:www-data /var/www/html/storage/framework/views
else
    echo "⚠️  View cache skipped (directory not writable)"
fi

echo "✅ Application ready!"
echo "📌 Access at: http://localhost:8000"
echo "🔐 Admin: admin@restaurant.com / password"

# Install Octane and Pulse if not already installed
if [ ! -f "config/octane.php" ]; then
    echo "📦 Installing Laravel Octane..."
    php artisan octane:install --server=swoole --no-interaction || echo "Octane install skipped"
    # Fix ownership after installation
    chown -R www-data:www-data /var/www/html
fi

if [ ! -f "config/pulse.php" ]; then
    echo "📦 Installing Laravel Pulse..."
    php artisan pulse:install --no-interaction || echo "Pulse install skipped"
    # Fix ownership after installation
    chown -R www-data:www-data /var/www/html
fi

# Run Pulse migrations if tables don't exist
if [ -f "config/pulse.php" ]; then
    echo "📊 Running Pulse migrations..."
    php artisan migrate --force 2>/dev/null || echo "Pulse migrations skipped"
    chown -R www-data:www-data /var/www/html/storage
    chown -R www-data:www-data /var/www/html/bootstrap/cache
fi

# Determine server type from environment variable (default: nginx)
SERVER_TYPE=${SERVER_TYPE:-nginx}

echo "🚦 Configuring services for server type: ${SERVER_TYPE}"

# Configure supervisor based on server type
/usr/local/bin/configure-supervisor.sh

echo "🚦 Starting services..."
if [ "$SERVER_TYPE" = "swoole" ]; then
    echo "   - Laravel Octane (Swoole)"
else
    echo "   - PHP-FPM"
    echo "   - Nginx"
fi
echo "   - Laravel Queue Workers (2 processes)"
echo "   - Laravel Scheduler (cron)"

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

