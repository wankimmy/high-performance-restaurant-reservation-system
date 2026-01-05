# Deployment Guide for Shared Hosting

This guide will help you deploy the Restaurant Reservation System on shared hosting.

## Pre-Deployment Checklist

- [ ] PHP 8.2+ installed
- [ ] MySQL/MariaDB database created
- [ ] Redis server available (or use hosting provider's Redis)
- [ ] SSH access (recommended) or FTP access
- [ ] Composer installed on server or locally

## Step 1: Upload Files

Upload all project files to your hosting directory (usually `public_html` or `www`).

**Important**: For shared hosting, you may need to:
- Place Laravel files in a subdirectory
- Point document root to `public` folder (if possible)
- Or use `.htaccess` to route requests properly

## Step 2: Install Dependencies

### Option A: Via SSH (Recommended)
```bash
cd /path/to/your/project
composer install --no-dev --optimize-autoloader
```

### Option B: Via Local Machine
```bash
composer install --no-dev --optimize-autoloader
# Then upload vendor folder
```

## Step 3: Configure Environment

1. Copy `.env.example` to `.env`
2. Update with your hosting credentials:

```env
APP_NAME="Restaurant Reservation System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost  # or your hosting provider's DB host
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

REDIS_HOST=localhost  # or your hosting provider's Redis host
REDIS_PASSWORD=null  # if required by hosting
REDIS_PORT=6379

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

3. Generate application key:
```bash
php artisan key:generate
```

## Step 4: Set Permissions

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

## Step 5: Run Migrations

```bash
php artisan migrate --force
```

## Step 6: Seed Sample Data (Optional)

```bash
php artisan db:seed
```

## Step 7: Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## Step 8: Set Up Queue Worker

### Option A: Cron Job (Recommended for Shared Hosting)

Add to your crontab:
```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

Create a custom command or use a cron job to run queue worker:
```bash
*/5 * * * * cd /path/to/your/project && php artisan queue:work redis --queue=reservations --tries=3 --timeout=60 >> /dev/null 2>&1
```

### Option B: Continuous Process (If Available)

If your hosting supports long-running processes:
```bash
php artisan queue:work redis --queue=reservations --daemon
```

## Step 9: Configure Web Server

### Apache Configuration

Ensure `.htaccess` is in the `public` directory and mod_rewrite is enabled.

If Laravel is in a subdirectory, update `.htaccess`:
```apache
RewriteBase /your-subdirectory/public
```

### Nginx Configuration (If Available)

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/your/project/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Step 10: Security Hardening

1. **Set proper file permissions:**
   ```bash
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   chmod -R 775 storage bootstrap/cache
   ```

2. **Protect sensitive files:**
   - Ensure `.env` is not publicly accessible
   - Verify `.gitignore` is working

3. **Enable HTTPS:**
   - Install SSL certificate
   - Update `APP_URL` in `.env` to use `https://`

## Step 11: Monitoring

### Check Queue Status
```bash
php artisan queue:monitor redis:reservations
```

### View Logs
```bash
tail -f storage/logs/laravel.log
```

### Monitor Redis
```bash
redis-cli info stats
```

## Troubleshooting

### Issue: 500 Internal Server Error
- Check file permissions
- Verify `.env` configuration
- Check `storage/logs/laravel.log` for errors
- Ensure PHP version is 8.2+

### Issue: Queue Not Processing
- Verify Redis connection
- Check if cron job is running
- Review queue worker logs

### Issue: Slow Performance
- Enable OPcache in PHP
- Check Redis memory usage
- Optimize database queries
- Review server resources

### Issue: Session/Cache Not Working
- Verify Redis is accessible
- Check Redis connection settings
- Test Redis connection: `redis-cli ping`

## Performance Tips for Shared Hosting

1. **Use Redis for caching** (already configured)
2. **Enable OPcache** in PHP settings
3. **Optimize autoloader**: `composer dump-autoload --optimize`
4. **Use CDN** for static assets (if needed)
5. **Monitor memory usage** and adjust queue workers accordingly
6. **Regular cleanup**: Clear old cache and logs periodically

## Maintenance Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Re-optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check application status
php artisan about
```

## Backup Strategy

1. **Database Backup:**
   ```bash
   mysqldump -u username -p database_name > backup.sql
   ```

2. **Files Backup:**
   - Backup `.env` file
   - Backup `storage/app` directory
   - Backup database regularly

## Support

For hosting-specific issues, consult your hosting provider's documentation or support team.

