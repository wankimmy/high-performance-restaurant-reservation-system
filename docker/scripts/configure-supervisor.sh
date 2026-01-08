#!/bin/bash

# Configure supervisor based on SERVER_TYPE environment variable
SERVER_TYPE=${SERVER_TYPE:-nginx}

echo "🔧 Configuring supervisor for server type: ${SERVER_TYPE}"

# Create a temporary supervisor config
TEMP_CONFIG="/tmp/supervisord.conf.tmp"
cp /etc/supervisor/conf.d/supervisord.conf "$TEMP_CONFIG"

if [ "$SERVER_TYPE" = "swoole" ]; then
    # Enable Octane, disable Nginx and PHP-FPM
    sed -i '/\[program:php-fpm\]/,/^$/s/^autostart=.*/autostart=false/' "$TEMP_CONFIG"
    sed -i '/\[program:nginx\]/,/^$/s/^autostart=.*/autostart=false/' "$TEMP_CONFIG"
    sed -i '/\[program:laravel-octane\]/,/^$/s/^autostart=.*/autostart=true/' "$TEMP_CONFIG"
else
    # Enable Nginx and PHP-FPM, disable Octane
    sed -i '/\[program:php-fpm\]/,/^$/s/^autostart=.*/autostart=true/' "$TEMP_CONFIG"
    sed -i '/\[program:nginx\]/,/^$/s/^autostart=.*/autostart=true/' "$TEMP_CONFIG"
    sed -i '/\[program:laravel-octane\]/,/^$/s/^autostart=.*/autostart=false/' "$TEMP_CONFIG"
fi

# Replace the original config
mv "$TEMP_CONFIG" /etc/supervisor/conf.d/supervisord.conf

echo "✅ Supervisor configuration updated"
