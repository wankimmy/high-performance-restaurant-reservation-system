# Restaurant Reservation System

> A high-performance, production-ready restaurant reservation system built with Laravel 11, Redis, and Docker.

[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://php.net)
[![Laravel 11](https://img.shields.io/badge/Laravel-11-red.svg)](https://laravel.com)
[![Filament 3](https://img.shields.io/badge/Filament-3.3-yellow.svg)](https://filamentphp.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-blue.svg)](https://docker.com)

---

## ✨ Features

### User Booking System
- 📅 **Date & Time Selection** - Bootstrap datepicker with full year/month display
- ⏰ **Time Selection** - Choose from available time slots
- 👥 **Party Size** - Specify number of guests (pax)
- 🪑 **Table Selection** - Visual grid of available tables with capacity matching
- 📝 **Special Requests** - Add notes (up to 100 characters)
- ✅ **Real-time Availability** - Check table status instantly based on date, time, and pax
- 🔐 **OTP Verification** - WhatsApp OTP verification for secure bookings
- 🔄 **Queue Processing** - Real-time reservation processing with queue number
- 🔒 **Secure Booking** - CSRF protection and validation

### Admin Dashboard (Powered by Laravel Filament)
- 🎨 **Modern UI** - Professional, clean interface with dark mode
- 📋 **Reservation Management** - View, edit, and cancel bookings
- 🗓️ **Date Control** - Open/close specific reservation dates
- 🪑 **Table Management** - Control table availability
- 📊 **Real-time Monitoring** - System health and metrics
- 📱 **Mobile Responsive** - Full admin functionality on any device
- 📥 **Export Data** - Export to Excel/CSV
- 🔍 **Advanced Search** - Filter, sort, and search everything
- 🔐 **Secure Authentication** - Laravel Sanctum

### Performance & Scalability
- ⚡ **High Traffic Support** - Handles up to 1M concurrent requests
- 🚀 **Redis Caching** - Lightning-fast data access
- 📬 **Queue System** - Async job processing
- 🔄 **Auto-scaling** - Ready for load balancing
- 💾 **Database Optimization** - Indexed queries and eager loading

### Security
- 🛡️ **CSRF Protection** - Cross-site request forgery prevention
- 🚫 **Bot Blocking** - Detects and blocks known bots
- ⏱️ **Rate Limiting** - 60 requests/minute per IP
- 🔒 **Spam Prevention** - Max 3 bookings/hour per IP
- 🔐 **XSS Protection** - Input sanitization
- 💉 **SQL Injection Protection** - Eloquent ORM

### Monitoring Dashboard
- 📊 **System Resources** - CPU, Memory, Disk usage
- 👥 **Active Users** - Real-time visitor tracking
- 📦 **Queue Metrics** - Jobs processed, pending, failed
- 👷 **Worker Status** - Queue worker health
- 💾 **Database Stats** - Connection pool and queries
- 🔴 **Redis Metrics** - Memory usage and connections
- 🔄 **Auto-refresh** - Updates every 60 seconds

---

## 🚀 Quick Start (2 Commands)

### Prerequisites
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows/Mac) or Docker Engine (Linux)
- Git

### Installation

```bash
# 1. Clone the repository
git clone <your-repo-url>
cd HPBS

# 2. Build Docker image
docker-compose build

# 3. Start all services
docker-compose up -d
```

**That's it!** The container automatically:
- ✅ Installs all Composer dependencies
- ✅ Generates application key
- ✅ **Fresh database setup** - Drops and recreates all tables (migrate:fresh)
- ✅ Seeds admin user and sample data
- ✅ Optimizes configuration
- ✅ Starts all services (Nginx, PHP-FPM, Queue Workers, Scheduler)

### Access the Application

| Service | URL | Credentials |
|---------|-----|-------------|
| **Booking Page** | http://localhost:8000 | (Public access) |
| **Admin Panel** 🎨 | http://localhost:8000/admin | admin@restaurant.com / password |
| **Monitoring** | http://localhost:8000/admin/monitoring | (After login) |
| **phpMyAdmin** | http://localhost:8080 | restaurant_user / restaurant_password |

**🎨 New Feature**: Admin panel now powered by **Laravel Filament** - Modern, professional UI!

**⚠️ Change the default password immediately after first login!**  
**⚠️ Remove phpMyAdmin in production or restrict access!**

---

## 📋 Project Structure

```
HPBS/
├── app/
│   ├── Console/Commands/      # Artisan commands
│   ├── Http/
│   │   ├── Controllers/       # API and web controllers
│   │   └── Middleware/        # Custom middleware
│   ├── Jobs/                  # Queue jobs
│   └── Models/                # Eloquent models
├── config/                    # Laravel configuration
├── database/
│   ├── migrations/            # Database schema
│   └── seeders/               # Database seeders
├── docker/
│   ├── nginx/                 # Nginx configuration
│   ├── php/                   # PHP-FPM configuration
│   ├── supervisor/            # Process manager config
│   └── scripts/               # Startup scripts
├── public/
│   └── css/                   # Stylesheets
├── resources/views/           # Blade templates
├── routes/                    # API and web routes
├── storage/                   # Logs and cache
└── docker-compose.yml         # Docker orchestration
```

---

## 🏗️ Architecture

### Single-Container Design

```
┌──────────────────────────────────────┐
│     Docker Container (app)           │
│  ┌────────────────────────────────┐  │
│  │  Supervisor (Process Manager)  │  │
│  └────────────────────────────────┘  │
│        │        │        │       │    │
│     ┌──▼──┐  ┌─▼──┐  ┌──▼─┐  ┌──▼─┐ │
│     │Nginx│  │PHP │  │Queue│ │Cron│  │
│     │:80  │  │-FPM│  │  x2 │ │Job │  │
│     └─────┘  └────┘  └────┘  └────┘  │
└──────────────────────────────────────┘
         │              │
    ┌────▼────┐    ┌───▼─────┐
    │ MySQL   │    │  Redis  │
    │  :3306  │    │  :6379  │
    └─────────┘    └─────────┘
```

### Service Stack
- **Web Server**: Nginx (port 80 → 8000)
- **Application**: PHP 8.4-FPM
- **Database**: MySQL 8.0
- **Cache/Queue**: Redis
- **Queue Workers**: 2 parallel processes (auto-started via Supervisor)
- **Scheduler**: Laravel schedule:work (auto-started via Supervisor)
- **Process Manager**: Supervisor (manages all services)

---

## 🔧 Common Commands

### Docker Management

```bash
# View all logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app

# Check container status
docker-compose ps

# Restart services
docker-compose restart

# Stop (keeps data)
docker-compose stop

# Start again
docker-compose start

# Complete reset (removes data)
docker-compose down -v
```

### Application Commands

```bash
# Access container shell
docker-compose exec app bash

# Run fresh migrations (drops all tables and re-runs migrations)
docker-compose exec app php artisan migrate:fresh

# Run migrations (incremental)
docker-compose exec app php artisan migrate

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Run database seeder
docker-compose exec app php artisan db:seed

# Access Tinker (REPL)
docker-compose exec app php artisan tinker

# View queue jobs
docker-compose exec app php artisan queue:work --once

# Check routes
docker-compose exec app php artisan route:list
```

### Service Management (Inside Container)

```bash
# Check all services status
docker-compose exec app supervisorctl status

# Restart specific service
docker-compose exec app supervisorctl restart nginx
docker-compose exec app supervisorctl restart laravel-queue:*

# View service logs
docker-compose exec app supervisorctl tail -f nginx
docker-compose exec app supervisorctl tail -f laravel-queue
```

---

## 🔍 Monitoring

### Access the Dashboard
Navigate to **http://localhost:8000/admin/monitoring** (requires login)

### Metrics Displayed
- **System**: CPU, Memory, Disk usage
- **Queue**: Jobs pending, processed, failed
- **Workers**: Status of queue workers
- **Visitors**: Real-time active users
- **Database**: Connection pool, query count
- **Redis**: Memory usage, connected clients

### Daily Reset
Metrics automatically reset daily via scheduled task:
```bash
docker-compose exec app php artisan metrics:reset
```

---

## ⚙️ Configuration

### Environment Variables

Key variables in `.env`:

```env
# Application
APP_NAME=RestaurantReservation
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_HOST=mysql
DB_DATABASE=restaurant_reservation
DB_USERNAME=restaurant_user
DB_PASSWORD=your_secure_password

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password

# Queue
QUEUE_CONNECTION=redis

# Session & Cache
SESSION_DRIVER=redis
CACHE_STORE=redis
```

### Customization

#### Change Number of Queue Workers
Edit `docker/supervisor/supervisord.conf`:
```ini
[program:laravel-queue]
numprocs=4  # Change from 2 to 4
```

#### Adjust Rate Limiting
Edit `app/Http/Middleware/RateLimitMiddleware.php`:
```php
$maxAttempts = 100;  // Change from 60
```

#### Modify Table Capacity
```bash
docker-compose exec app php artisan tinker
> \App\Models\Table::create(['name' => 'VIP Table', 'capacity' => 8, 'status' => 'available']);
```

---

## 🚀 Production Deployment

### Pre-deployment Checklist

1. **Environment**
   - [ ] Set `APP_ENV=production`
   - [ ] Set `APP_DEBUG=false`
   - [ ] Generate new `APP_KEY`
   - [ ] Use strong database passwords
   - [ ] Configure Redis password

2. **Security**
   - [ ] Change default admin password
   - [ ] Set up SSL/TLS certificate
   - [ ] Configure firewall rules
   - [ ] Enable HTTPS redirect
   - [ ] Set secure session cookies

3. **Performance**
   - [ ] Enable OpCache
   - [ ] Configure Redis persistence
   - [ ] Set up database backups
   - [ ] Configure log rotation
   - [ ] Set up CDN for static assets

4. **Monitoring**
   - [ ] Set up external monitoring
   - [ ] Configure error reporting (Sentry, Bugsnag)
   - [ ] Set up uptime monitoring
   - [ ] Configure alerts
   - [ ] Set up backup verification

### Deployment Steps

See [`DEPLOYMENT.md`](DEPLOYMENT.md) for detailed production deployment guide.

---

## 🐛 Troubleshooting

### Container Won't Start

```bash
# Check logs for errors
docker-compose logs app

# Rebuild without cache
docker-compose build --no-cache

# Complete reset
docker-compose down -v
docker-compose up -d
```

### Database Connection Failed

```bash
# Check MySQL is running
docker-compose ps mysql

# Check MySQL logs
docker-compose logs mysql

# Restart MySQL
docker-compose restart mysql

# Wait 30 seconds for initialization
```

### Queue Not Processing

```bash
# Check worker status (workers auto-start on container startup)
docker-compose exec app supervisorctl status laravel-queue:*

# Check scheduler status (scheduler auto-starts on container startup)
docker-compose exec app supervisorctl status laravel-scheduler

# Restart workers
docker-compose exec app supervisorctl restart laravel-queue:*

# Restart scheduler
docker-compose exec app supervisorctl restart laravel-scheduler

# Check for failed jobs
docker-compose exec app php artisan queue:failed
```

### Permission Issues

```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### High CPU Usage

```bash
# Check running processes
docker-compose exec app top

# Check queue size
docker-compose exec app php artisan queue:monitor

# Scale down workers if needed
# Edit docker/supervisor/supervisord.conf
```

---

## 🧪 Testing

### Run Tests

```bash
# PHPUnit tests
docker-compose exec app php artisan test

# Specific test file
docker-compose exec app php artisan test --filter ReservationTest

# With coverage
docker-compose exec app php artisan test --coverage
```

### Create Test Reservation

```bash
docker-compose exec app php artisan tinker
> $table = \App\Models\Table::first();
> \App\Models\Reservation::create([
    'table_id' => $table->id,
    'customer_name' => 'Test User',
    'customer_email' => 'test@example.com',
    'customer_phone' => '1234567890',
    'pax' => 4,
    'reservation_date' => now()->addDay(),
    'reservation_time' => '19:00:00',
    'notes' => 'Test reservation',
    'status' => 'confirmed'
]);
```

---

## 💾 Database Management

### phpMyAdmin Access

A web-based MySQL management tool is included for easy database administration.

- **URL**: http://localhost:8080
- **Username**: `restaurant_user`
- **Password**: `restaurant_password`

**Features**:
- Browse and edit tables
- Run SQL queries
- Import/Export database
- View table structures
- Create backups

See [`PHPMYADMIN.md`](PHPMYADMIN.md) for complete guide.

**⚠️ Security Warning**: Remove or restrict phpMyAdmin in production!

---

## 📚 API Documentation

### Public Endpoints

#### Check Availability
```http
GET /api/v1/availability?date=2024-01-15&time=19:00&pax=4
```

#### Create Reservation
```http
POST /api/v1/reservations
Content-Type: application/json

{
  "table_id": 1,
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "customer_phone": "1234567890",
  "pax": 4,
  "reservation_date": "2024-01-15",
  "reservation_time": "19:00",
  "notes": "Window seat preferred"
}
```

#### Verify OTP
```http
POST /api/v1/verify-otp
Content-Type: application/json

{
  "session_id": "uuid-here",
  "otp_code": "123456"
}
```

#### Resend OTP
```http
POST /api/v1/resend-otp
Content-Type: application/json

{
  "session_id": "uuid-here"
}
```

#### Check Reservation Status
```http
GET /api/v1/reservation-status?session_id=uuid-here
```

### Booking Flow
1. User selects date, time, pax, and table
2. User submits booking form → Redirects to `/verify-otp`
3. User enters OTP sent via WhatsApp
4. After verification → Redirects to `/queue` (shows queue number)
5. Queue page polls for status → Redirects to result page

### Rate Limits
- **Public API**: 60 requests per minute per IP
- **Reservations**: 3 per hour per IP

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).

---

## 🆘 Support

### Documentation
- **Quick Start**: [`START_HERE.md`](START_HERE.md) - Get started in 2 minutes
- **Deployment**: [`DEPLOYMENT.md`](DEPLOYMENT.md) - Production setup guide
- **Contributing**: [`CONTRIBUTING.md`](CONTRIBUTING.md) - Development guidelines
- **phpMyAdmin**: [`PHPMYADMIN.md`](PHPMYADMIN.md) - Database management guide
- **Project Overview**: [`PROJECT_OVERVIEW.md`](PROJECT_OVERVIEW.md) - Complete structure

### External Resources
- **Laravel Docs**: https://laravel.com/docs
- **Docker Docs**: https://docs.docker.com
- **phpMyAdmin Docs**: https://docs.phpmyadmin.net

---

## 🎉 Credits

Built with:
- [Laravel 11](https://laravel.com) - PHP Framework
- [Laravel Breeze](https://laravel.com/breeze) - Authentication scaffolding
- [Bootstrap 5](https://getbootstrap.com) - Frontend framework
- [Bootstrap Datepicker](https://github.com/uxsolutions/bootstrap-datepicker) - Date selection
- [Redis](https://redis.io) - In-memory data store
- [MySQL](https://mysql.com) - Database
- [Nginx](https://nginx.org) - Web server
- [Docker](https://docker.com) - Containerization
- [Supervisor](http://supervisord.org) - Process management

---

**Made with ❤️ for restaurants everywhere**
