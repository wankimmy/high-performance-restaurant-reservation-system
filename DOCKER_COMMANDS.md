# Docker Commands Quick Reference

## Setup & Start

```bash
# Automated setup (recommended)
./docker-setup.sh

# Manual setup
cp .env.docker .env
docker-compose build
docker-compose up -d
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

## Container Management

```bash
# Start all containers
docker-compose up -d

# Stop all containers
docker-compose stop

# Restart all containers
docker-compose restart

# Stop and remove containers (keeps data)
docker-compose down

# Remove containers and volumes (deletes all data!)
docker-compose down -v

# View running containers
docker-compose ps

# View container logs
docker-compose logs -f
docker-compose logs -f app        # Specific service
docker-compose logs -f nginx
docker-compose logs -f queue
```

## Application Commands

```bash
# Run artisan commands
docker-compose exec app php artisan [command]

# Common artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Open Laravel Tinker
docker-compose exec app php artisan tinker

# Run queue worker manually
docker-compose exec app php artisan queue:work
```

## Database Commands

```bash
# Access MySQL CLI
docker-compose exec mysql mysql -u restaurant_user -p

# Backup database
docker-compose exec mysql mysqldump -u restaurant_user -p restaurant_reservation > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u restaurant_user -p restaurant_reservation < backup.sql

# View database tables
docker-compose exec mysql mysql -u restaurant_user -p -e "USE restaurant_reservation; SHOW TABLES;"
```

## Redis Commands

```bash
# Access Redis CLI
docker-compose exec redis redis-cli

# Test Redis
docker-compose exec redis redis-cli ping

# View all keys
docker-compose exec redis redis-cli KEYS '*'

# Clear all Redis data
docker-compose exec redis redis-cli FLUSHALL

# Monitor Redis commands
docker-compose exec redis redis-cli MONITOR
```

## File System

```bash
# Fix permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache

# Access container shell
docker-compose exec app bash
docker-compose exec app sh

# Copy files from container
docker cp restaurant_app:/var/www/html/storage/logs/laravel.log ./local-laravel.log

# Copy files to container
docker cp ./local-file.txt restaurant_app:/var/www/html/
```

## Monitoring & Debugging

```bash
# Monitor resource usage
docker stats

# View container processes
docker-compose top

# Inspect container
docker-compose exec app php -v
docker-compose exec app php -m
docker-compose exec app composer --version

# Check PHP info
docker-compose exec app php -i

# View Nginx configuration
docker-compose exec nginx nginx -t
```

## Optimization

```bash
# Cache configuration
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app php artisan event:cache

# Clear all caches
docker-compose exec app php artisan optimize:clear

# Optimize composer autoload
docker-compose exec app composer dump-autoload --optimize
```

## Scaling

```bash
# Scale queue workers
docker-compose up -d --scale queue=3

# View scaled services
docker-compose ps
```

## Troubleshooting

```bash
# Rebuild containers
docker-compose build --no-cache

# Restart specific service
docker-compose restart app
docker-compose restart nginx
docker-compose restart mysql

# Remove and recreate volumes
docker-compose down -v
docker-compose up -d

# View full logs
docker-compose logs --tail=100 app

# Check service health
docker-compose ps
docker inspect restaurant_app | grep -A 5 Health
```

## Cleanup

```bash
# Remove stopped containers
docker-compose rm

# Remove unused images
docker image prune -a

# Remove unused volumes
docker volume prune

# Full system cleanup (use with caution!)
docker system prune -a --volumes
```

## Development Workflow

```bash
# Start development
docker-compose up -d
docker-compose logs -f app

# Make code changes (changes reflect immediately)

# Clear caches after config changes
docker-compose exec app php artisan config:clear

# Run migrations
docker-compose exec app php artisan migrate

# View logs
docker-compose logs -f
```

## Production Commands

```bash
# Start in production
docker-compose -f docker-compose.prod.yml up -d

# Optimize for production
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app composer install --no-dev --optimize-autoloader

# Monitor
docker-compose logs -f --tail=100
```

## Emergency Commands

```bash
# Force restart everything
docker-compose down
docker-compose up -d --force-recreate

# Reset database (WARNING: Deletes all data!)
docker-compose exec app php artisan migrate:fresh --seed

# Emergency stop
docker-compose kill

# View error logs
docker-compose logs --tail=100 app
docker-compose logs --tail=100 nginx
docker-compose logs --tail=100 mysql
```

## Useful Aliases

Add to your `.bashrc` or `.zshrc`:

```bash
alias dc='docker-compose'
alias dcu='docker-compose up -d'
alias dcd='docker-compose down'
alias dcl='docker-compose logs -f'
alias dce='docker-compose exec'
alias dca='docker-compose exec app php artisan'
```

Usage:
```bash
dcu              # Start containers
dcl app          # View app logs
dca migrate      # Run migrations
dce app bash     # Access app shell
```

