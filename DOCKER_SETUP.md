# Docker Setup Guide

## Overview

This repository includes a complete Docker setup for the Restaurant Reservation System with all required services containerized.

## Prerequisites

- **Docker**: Version 20.10 or higher
- **Docker Compose**: Version 2.0 or higher
- **Operating System**: Linux, macOS, or Windows with WSL2

## Quick Start

### 1. Automated Setup (Recommended)

Run the setup script to automatically configure everything:

```bash
# Linux/macOS
chmod +x docker-setup.sh
./docker-setup.sh

# Windows (Git Bash or WSL)
bash docker-setup.sh
```

This script will:
- Create `.env` file from `.env.docker`
- Build Docker containers
- Start all services
- Run database migrations
- Seed the database with default admin user
- Optimize the application

### 2. Manual Setup

If you prefer manual setup:

```bash
# Copy environment file
cp .env.docker .env

# Build containers
docker-compose build

# Start containers
docker-compose up -d

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Seed database
docker-compose exec app php artisan db:seed

# Cache configuration
docker-compose exec app php artisan config:cache
```

## Services

### Container Overview

| Service | Container Name | Port | Description |
|---------|----------------|------|-------------|
| Nginx | `restaurant_nginx` | 8000 | Web server |
| PHP-FPM | `restaurant_app` | 9000 | Laravel application |
| MySQL | `restaurant_mysql` | 3306 | Database |
| Redis | `restaurant_redis` | 6379 | Cache & Queue |
| Queue Worker | `restaurant_queue` | - | Background job processor |
| Scheduler | `restaurant_scheduler` | - | Task scheduler |

### Service Details

**Nginx (Web Server)**
- Serves static files and proxies PHP requests
- Optimized with gzip compression
- Security headers configured
- Static asset caching

**PHP-FPM (Application)**
- PHP 8.2 with all required extensions
- Composer installed
- OPcache enabled for performance
- Session storage in Redis

**MySQL (Database)**
- MySQL 8.0
- Optimized configuration for performance
- Persistent volume for data
- Health checks enabled

**Redis (Cache & Queue)**
- In-memory data store
- Used for caching, sessions, and queues
- Persistent storage with AOF
- Health checks enabled

**Queue Worker**
- Processes reservation jobs
- Auto-restart on failure
- 3 retry attempts
- 60-second timeout

**Scheduler**
- Runs Laravel scheduled tasks
- Executes every minute
- Daily metrics reset

## Access Points

After setup, access the application at:

- **Booking Page**: http://localhost:8000
- **Admin Login**: http://localhost:8000/login
- **Admin Dashboard**: http://localhost:8000/admin/reservations

### Default Admin Credentials

```
Email: admin@restaurant.com
Password: password
```

⚠️ **Change the password after first login!**

## Common Commands

### Container Management

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose stop

# Restart containers
docker-compose restart

# Remove containers (keeps data)
docker-compose down

# Remove containers and volumes (deletes data!)
docker-compose down -v

# View running containers
docker-compose ps

# View logs (all services)
docker-compose logs -f

# View logs (specific service)
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f mysql
docker-compose logs -f redis
docker-compose logs -f queue
```

### Laravel Artisan Commands

```bash
# Run artisan commands
docker-compose exec app php artisan [command]

# Examples:
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan queue:work
docker-compose exec app php artisan tinker
```

### Database Commands

```bash
# Access MySQL CLI
docker-compose exec mysql mysql -u restaurant_user -p restaurant_reservation

# Export database
docker-compose exec mysql mysqldump -u restaurant_user -p restaurant_reservation > backup.sql

# Import database
docker-compose exec -T mysql mysql -u restaurant_user -p restaurant_reservation < backup.sql
```

### Redis Commands

```bash
# Access Redis CLI
docker-compose exec redis redis-cli

# Check Redis status
docker-compose exec redis redis-cli ping

# Flush all Redis data
docker-compose exec redis redis-cli FLUSHALL
```

### File Permissions

```bash
# Fix storage permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

## Configuration

### Environment Variables

Main configuration is in `.env` file. Key Docker-specific settings:

```env
DB_HOST=mysql          # MySQL container name
REDIS_HOST=redis       # Redis container name
APP_URL=http://localhost:8000
```

### PHP Configuration

Edit `docker/php/local.ini` to customize PHP settings:
- Memory limit
- Upload size
- Execution time
- OPcache settings

### MySQL Configuration

Edit `docker/mysql/my.cnf` to customize MySQL:
- Connection limits
- Buffer sizes
- Performance tuning

### Nginx Configuration

Edit `docker/nginx/conf.d/default.conf` to customize:
- Server names
- SSL certificates
- Proxy settings
- Cache rules

## Troubleshooting

### Containers Won't Start

```bash
# Check container logs
docker-compose logs

# Check specific service
docker-compose logs app
docker-compose logs mysql

# Rebuild containers
docker-compose build --no-cache
docker-compose up -d
```

### Permission Issues

```bash
# Fix ownership
docker-compose exec app chown -R www-data:www-data /var/www/html

# Fix permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Database Connection Issues

```bash
# Check MySQL is running
docker-compose ps mysql

# Check MySQL logs
docker-compose logs mysql

# Verify credentials in .env
cat .env | grep DB_
```

### Redis Connection Issues

```bash
# Check Redis is running
docker-compose ps redis

# Test Redis connection
docker-compose exec redis redis-cli ping
```

### Application Errors

```bash
# View Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Queue Not Processing

```bash
# Check queue worker status
docker-compose ps queue

# Restart queue worker
docker-compose restart queue

# View queue worker logs
docker-compose logs -f queue
```

### Port Already in Use

If port 8000 is already in use, edit `docker-compose.yml`:

```yaml
nginx:
  ports:
    - "8080:80"  # Change 8000 to 8080 (or any available port)
```

Then restart:
```bash
docker-compose down
docker-compose up -d
```

## Performance Optimization

### For Development

Use bind mounts (default configuration) for hot-reloading:
```yaml
volumes:
  - ./:/var/www/html
```

### For Production

1. **Build optimized image**:
```bash
docker-compose -f docker-compose.prod.yml build
```

2. **Use volume mounts for storage only**
3. **Enable OPcache** (already configured)
4. **Use Redis for sessions** (already configured)
5. **Cache configuration**:
```bash
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

## Scaling

### Scale Queue Workers

```bash
# Scale to 3 queue workers
docker-compose up -d --scale queue=3

# Scale back to 1
docker-compose up -d --scale queue=1
```

### Load Balancing

For production, consider:
- Multiple application containers
- Load balancer (Nginx/HAProxy)
- Separate Redis instances for cache and queue

## Backup & Restore

### Database Backup

```bash
# Create backup
docker-compose exec mysql mysqldump -u restaurant_user -p restaurant_reservation > backup_$(date +%Y%m%d).sql

# Restore backup
docker-compose exec -T mysql mysql -u restaurant_user -p restaurant_reservation < backup_20240101.sql
```

### Volume Backup

```bash
# Backup MySQL data
docker run --rm -v restaurant_mysql_data:/data -v $(pwd):/backup ubuntu tar czf /backup/mysql-backup.tar.gz /data

# Restore MySQL data
docker run --rm -v restaurant_mysql_data:/data -v $(pwd):/backup ubuntu tar xzf /backup/mysql-backup.tar.gz -C /
```

## Security Best Practices

1. **Change default passwords** in `.env`
2. **Use environment-specific files** (`.env.production`)
3. **Enable HTTPS** with SSL certificates
4. **Restrict network access** in `docker-compose.yml`
5. **Regular updates**: `docker-compose pull`
6. **Scan images**: `docker scan restaurant_app`
7. **Use secrets** for sensitive data (Docker Swarm/Kubernetes)

## Production Deployment

### Using Docker Compose

1. Create production environment file
2. Update `docker-compose.yml` for production settings
3. Use secrets management
4. Set up SSL/TLS
5. Configure monitoring
6. Set up automated backups

### Using Kubernetes

Consider migrating to Kubernetes for:
- High availability
- Auto-scaling
- Rolling updates
- Better orchestration

## Monitoring

### Container Health

```bash
# Check container health
docker-compose ps

# Monitor resource usage
docker stats
```

### Application Logs

```bash
# Follow all logs
docker-compose logs -f

# Follow specific service
docker-compose logs -f app
```

### Performance Monitoring

Access the monitoring dashboard:
http://localhost:8000/admin/monitoring

## Support

For Docker-specific issues:
1. Check container logs: `docker-compose logs`
2. Verify services are running: `docker-compose ps`
3. Check resource usage: `docker stats`
4. Review Docker documentation: https://docs.docker.com

For application issues, see main README.md.

