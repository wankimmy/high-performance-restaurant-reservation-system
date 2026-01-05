#!/bin/bash
# Fix permissions for Laravel storage directories
# This script can be run periodically to ensure correct permissions

echo "🔒 Fixing Laravel storage permissions..."

# Fix ownership
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

# Fix permissions (775 for directories, 664 for files)
find /var/www/html/storage -type d -exec chmod 775 {} \;
find /var/www/html/storage -type f -exec chmod 664 {} \;
find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;
find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \;

# Ensure critical directories are writable
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

echo "✅ Permissions fixed successfully"

