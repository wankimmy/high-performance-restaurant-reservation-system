# Production Deployment Guide

> Complete guide for deploying the Restaurant Reservation System to production.

---

## 📋 Pre-Deployment Checklist

### Security
- [ ] Change all default passwords
- [ ] Generate new `APP_KEY` for production
- [ ] Set strong database passwords (16+ characters)
- [ ] Configure Redis password authentication
- [ ] Set up SSL/TLS certificates
- [ ] Configure firewall (allow only 80, 443, SSH)
- [ ] Disable root SSH login
- [ ] Set up fail2ban for brute force protection
- [ ] Enable HTTPS redirect in Nginx
- [ ] Set secure session cookies (`SESSION_SECURE_COOKIE=true`)

### Environment
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure proper `APP_URL`
- [ ] Set up email service (SMTP/SendGrid/AWS SES)
- [ ] Configure error tracking (Sentry, Bugsnag, etc.)
- [ ] Set up log management (Papertrail, CloudWatch)

### Performance
- [ ] Enable Redis persistence (AOF/RDB)
- [ ] Configure OpCache for PHP
- [ ] Set up database query caching
- [ ] Configure CDN for static assets
- [ ] Enable Gzip/Brotli compression
- [ ] Set appropriate PHP memory limits
- [ ] Configure connection pooling

### Backup & Recovery
- [ ] Set up automated MySQL backups
- [ ] Configure off-site backup storage (S3, Azure Blob)
- [ ] Test backup restoration process
- [ ] Set up Redis snapshots
- [ ] Configure volume backups
- [ ] Document recovery procedures

### Monitoring
- [ ] Set up uptime monitoring (UptimeRobot, Pingdom)
- [ ] Configure application monitoring (New Relic, Datadog)
- [ ] Set up error alerts
- [ ] Configure resource alerts (CPU, Memory, Disk)
- [ ] Set up SSL certificate expiry alerts
- [ ] Configure backup verification alerts

---

## 🖥️ Server Requirements

### Minimum Specifications
- **CPU**: 2 cores
- **RAM**: 4 GB
- **Disk**: 20 GB SSD
- **OS**: Ubuntu 22.04 LTS or later
- **Docker**: 24.0+ with Docker Compose
- **Network**: Static IP address

### Recommended Specifications
- **CPU**: 4+ cores
- **RAM**: 8+ GB
- **Disk**: 50+ GB SSD (NVMe preferred)
- **OS**: Ubuntu 22.04 LTS
- **Load Balancer**: For multiple app instances

---

## 🚀 Deployment Steps

### 1. Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Add user to docker group
sudo usermod -aG docker $USER
newgrp docker

# Install Git
sudo apt install -y git
```

### 2. Clone Repository

```bash
# Clone the project
cd /var/www
sudo git clone <your-repo-url> restaurant-system
cd restaurant-system
sudo chown -R $USER:$USER /var/www/restaurant-system
```

### 3. Configure Environment

```bash
# Create production .env file
cp .env.example .env
nano .env
```

**Production `.env` configuration:**

```env
# Application
APP_NAME=RestaurantReservation
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_KEY=base64:YOUR_GENERATED_KEY_HERE

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=restaurant_reservation
DB_USERNAME=restaurant_user
DB_PASSWORD=YOUR_STRONG_DB_PASSWORD_HERE

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=YOUR_REDIS_PASSWORD_HERE
REDIS_PORT=6379

# Cache & Session
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

# Queue
QUEUE_CONNECTION=redis

# Mail (Example: SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
LOG_SLACK_WEBHOOK_URL=your-slack-webhook-url

# Error Tracking (Optional)
SENTRY_LARAVEL_DSN=your-sentry-dsn
```

### 4. Configure Production Docker

Update `docker-compose.yml` for production:

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    restart: always
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 4G
        reservations:
          cpus: '1'
          memory: 2G
    # ... rest of config
```

### 5. Build and Deploy

```bash
# Build for production
docker-compose build --no-cache

# Start services
docker-compose up -d

# Wait for services to be healthy
sleep 30

# Verify all containers are running
docker-compose ps
```

### 6. Initial Setup

```bash
# Generate application key (if not set)
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate --force

# Seed admin user
docker-compose exec app php artisan db:seed --force --class=AdminUserSeeder

# Optimize for production
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app php artisan optimize
```

### 7. SSL Configuration

#### Option A: Let's Encrypt with Certbot

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Stop Nginx container temporarily
docker-compose stop app

# Generate certificate
sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com

# Update Nginx config to use SSL
# Edit docker/nginx/conf.d/default.conf

# Start app again
docker-compose start app
```

Update `docker/nginx/conf.d/default.conf`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    
    # ... rest of config
}
```

Mount certificates in `docker-compose.yml`:

```yaml
volumes:
  - /etc/letsencrypt:/etc/letsencrypt:ro
```

#### Option B: Cloudflare (Easiest)

1. Add your domain to Cloudflare
2. Enable "Full (Strict)" SSL mode
3. Enable "Always Use HTTPS"
4. Done! Cloudflare handles SSL

---

## 🔧 Post-Deployment Configuration

### 1. Change Admin Password

```bash
docker-compose exec app php artisan tinker

> $admin = \App\Models\User::where('email', 'admin@restaurant.com')->first();
> $admin->password = bcrypt('YOUR_NEW_STRONG_PASSWORD');
> $admin->save();
```

### 2. Set Up Cron (for scheduler)

The scheduler runs inside the container via Supervisor, no host cron needed!

### 3. Configure Firewall

```bash
# Allow HTTP, HTTPS, SSH
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Check status
sudo ufw status
```

### 4. Set Up Log Rotation

Create `/etc/logrotate.d/restaurant-system`:

```
/var/www/restaurant-system/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
    postrotate
        docker-compose -f /var/www/restaurant-system/docker-compose.yml exec -T app php artisan cache:clear > /dev/null 2>&1 || true
    endscript
}
```

### 5. Set Up Automated Backups

Create `/usr/local/bin/backup-restaurant-db.sh`:

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/restaurant-system"
DATE=$(date +%Y%m%d_%H%M%S)
KEEP_DAYS=30

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
docker-compose -f /var/www/restaurant-system/docker-compose.yml exec -T mysql \
  mysqldump -u restaurant_user -p'YOUR_DB_PASSWORD' restaurant_reservation \
  > $BACKUP_DIR/db_backup_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/db_backup_$DATE.sql

# Remove old backups
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +$KEEP_DAYS -delete

# Upload to S3 (optional)
# aws s3 cp $BACKUP_DIR/db_backup_$DATE.sql.gz s3://your-bucket/backups/
```

Add to crontab:

```bash
# Run daily at 2 AM
0 2 * * * /usr/local/bin/backup-restaurant-db.sh
```

---

## 📊 Monitoring Setup

### Application Monitoring

Edit `.env`:

```env
# New Relic (example)
NEW_RELIC_LICENSE_KEY=your-key
NEW_RELIC_APP_NAME=RestaurantSystem

# Or Datadog
DD_API_KEY=your-key
DD_ENV=production
DD_SERVICE=restaurant-system
```

### Health Check Endpoint

Already built-in at: `/api/health`

Set up external monitoring:
- UptimeRobot: https://uptimerobot.com
- Pingdom: https://pingdom.com
- StatusCake: https://statuscake.com

### Resource Monitoring

```bash
# Install monitoring agent (example: Netdata)
bash <(curl -Ss https://my-netdata.io/kickstart.sh)
```

---

## 🔄 Updates & Maintenance

### Application Updates

```bash
cd /var/www/restaurant-system

# Pull latest code
git pull origin main

# Rebuild containers
docker-compose build --no-cache

# Update with zero downtime
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force

# Clear and recache
docker-compose exec app php artisan optimize:clear
docker-compose exec app php artisan optimize
```

### Database Maintenance

```bash
# Optimize tables
docker-compose exec mysql mysqlcheck -u root -p --optimize restaurant_reservation

# Analyze tables
docker-compose exec mysql mysqlcheck -u root -p --analyze restaurant_reservation
```

### Redis Maintenance

```bash
# Check memory usage
docker-compose exec redis redis-cli INFO memory

# Clear cache if needed
docker-compose exec app php artisan cache:clear
```

---

## 📈 Scaling

### Horizontal Scaling

1. **Set up load balancer** (Nginx, HAProxy, AWS ALB)
2. **Deploy multiple app containers** on different servers
3. **Use external MySQL & Redis** (AWS RDS, ElastiCache)
4. **Share sessions** via Redis
5. **Use shared storage** for uploads (S3, NFS)

### Vertical Scaling

```yaml
# docker-compose.yml
services:
  app:
    deploy:
      resources:
        limits:
          cpus: '4'      # Increase from 2
          memory: 8G     # Increase from 4G
```

Increase queue workers:

```ini
# docker/supervisor/supervisord.conf
[program:laravel-queue]
numprocs=4  # Increase from 2
```

---

## 🐛 Troubleshooting Production

### High CPU Usage

```bash
# Check processes
docker-compose exec app top

# Check queue size
docker-compose exec app php artisan queue:monitor

# Scale workers
# Edit docker/supervisor/supervisord.conf and increase numprocs
```

### Database Slow

```bash
# Check slow queries
docker-compose exec mysql mysql -u root -p -e "SHOW PROCESSLIST;"

# Enable slow query log
docker-compose exec mysql mysql -u root -p -e "SET GLOBAL slow_query_log = 'ON';"
```

### Redis Memory Full

```bash
# Check memory
docker-compose exec redis redis-cli INFO memory

# Clear cache
docker-compose exec app php artisan cache:clear

# Increase Redis memory limit in docker-compose.yml
```

### Out of Disk Space

```bash
# Check disk usage
df -h

# Clean old logs
docker-compose exec app php artisan log:clear

# Clean old Docker images
docker system prune -a

# Rotate logs manually
logrotate -f /etc/logrotate.d/restaurant-system
```

---

## 🔐 Security Best Practices

1. **Keep system updated**: `sudo apt update && sudo apt upgrade`
2. **Monitor logs**: `docker-compose logs -f | grep -i error`
3. **Use secrets**: Never commit `.env` to Git
4. **Limit access**: Use VPN for admin access
5. **Regular backups**: Automate and test restores
6. **SSL certificates**: Keep renewed (Certbot auto-renews)
7. **Scan for vulnerabilities**: `docker scan hpbs-app`
8. **Monitor failed logins**: Check auth logs
9. **Rate limiting**: Already configured
10. **CSRF tokens**: Already enabled

---

## 📞 Support

- **Documentation**: Full [`README.md`](README.md)
- **Quick Start**: [`START_HERE.md`](START_HERE.md)
- **Laravel Docs**: https://laravel.com/docs
- **Docker Docs**: https://docs.docker.com

---

**Good luck with your deployment! 🚀**
