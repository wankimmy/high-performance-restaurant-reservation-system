#!/bin/bash

# Configure supervisor based on SERVER_TYPE environment variable
SERVER_TYPE=${SERVER_TYPE:-nginx}

echo "🔧 Configuring supervisor for server type: ${SERVER_TYPE}"

# Create a temporary supervisor config
TEMP_CONFIG="/tmp/supervisord.conf.tmp"
cp /etc/supervisor/conf.d/supervisord.conf "$TEMP_CONFIG"

# Function to update autostart for a program section
# Uses awk for more reliable section matching that doesn't depend on blank lines
# Matches from [program:xxx] to the next section header ([program: or [supervisord])
update_autostart() {
    local program_name="$1"
    local autostart_value="$2"
    local config_file="$3"
    
    # Use awk for more reliable section matching
    # Match from [program:xxx] to the next [program: or [supervisord] section header
    awk -v program="[program:${program_name}]" \
        -v autostart="${autostart_value}" \
        'BEGIN { 
            in_section = 0
        }
        {
            # Store original line for output
            original_line = $0
            
            # Remove carriage returns and trailing whitespace for matching
            line = $0
            gsub(/\r/, "", line)
            gsub(/[ \t]+$/, "", line)
            
            # Check if we are entering the target program section
            if (line == program) {
                in_section = 1
                print original_line
                next
            }
            
            # Check if we are entering a new section (end of current section)
            if (in_section && (line ~ /^\[program:/ || line ~ /^\[supervisord\]/)) {
                in_section = 0
            }
            
            # If we are in the target section and this is the autostart line, update it
            if (in_section && line ~ /^autostart=/) {
                print "autostart=" autostart
                next
            }
            
            # Print all other lines as-is
            print original_line
        }' "$config_file" > "${config_file}.tmp" && mv "${config_file}.tmp" "$config_file"
}

if [ "$SERVER_TYPE" = "swoole" ]; then
    # Enable Octane, disable Nginx and PHP-FPM
    update_autostart "php-fpm" "false" "$TEMP_CONFIG"
    update_autostart "nginx" "false" "$TEMP_CONFIG"
    update_autostart "laravel-octane" "true" "$TEMP_CONFIG"
else
    # Enable Nginx and PHP-FPM, disable Octane
    update_autostart "php-fpm" "true" "$TEMP_CONFIG"
    update_autostart "nginx" "true" "$TEMP_CONFIG"
    update_autostart "laravel-octane" "false" "$TEMP_CONFIG"
fi

# Replace the original config
mv "$TEMP_CONFIG" /etc/supervisor/conf.d/supervisord.conf

echo "✅ Supervisor configuration updated"
