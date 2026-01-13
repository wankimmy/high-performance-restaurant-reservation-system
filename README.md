# Restaurant Reservation System

> A high-performance restaurant reservation system built with Laravel 11, Redis, and Docker.

[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://php.net)
[![Laravel 11](https://img.shields.io/badge/Laravel-11-red.svg)](https://laravel.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-blue.svg)](https://docker.com)

---

## ✨ Features

### Booking System
- 📅 Date & time selection with dynamic time slots
- 🪑 Visual table selection with real-time availability
- 💰 Automatic deposit calculation
- 🔐 WhatsApp OTP verification
- 📝 Special requests and notes

### Admin Dashboard
- 📋 Reservation management with search and filters
- ✅ Two-step arrival verification via WhatsApp OTP
- ⚙️ Restaurant settings (hours, intervals, deposits)
- 🗓️ Date-specific settings (per-date hours, intervals, deposits)
- 🪑 Table management with availability checking
- 📱 WhatsApp Web integration

### Performance
- ⚡ High-performance Swoole/Octane server
- 💨 Redis caching and queue processing
- 🔄 Auto-scaling ready
- 📬 Async job processing

---

## 🚀 Quick Start

### Prerequisites
- Docker Desktop (Windows/Mac) or Docker Engine (Linux)
- Git

### Installation

```bash
# 1. Clone the repository
git clone <your-repo-url>
cd high-performance-restaurant-reservation-system

# 2. Create .env file
cp .env.example .env

# 3. Update .env with your database credentials
# Edit .env and set:
# DB_DATABASE=restaurant_reservation
# DB_USERNAME=restaurant_user
# DB_PASSWORD=your_secure_password

# 4. Build and start containers
docker-compose build
docker-compose up -d

# 5. Watch the startup logs
docker-compose logs -f app
```

**That's it!** The container automatically handles:
- ✅ Composer dependency installation
- ✅ APP_KEY generation
- ✅ Database migrations & seeding (first run)
- ✅ Vite asset compilation
- ✅ Configuration caching
- ✅ Starting Octane server + queue workers + scheduler

### Access

| Service | URL | Credentials |
|---------|-----|-------------|
| **Booking Page** | http://localhost:8000 | (Public) |
| **Admin Panel** | http://localhost:8000/admin/dashboard | admin@restaurant.com / password |
| **phpMyAdmin** | http://localhost:8080 | restaurant_user / restaurant_password |

**⚠️ Change the default password after first login!**

---

## 🔧 Common Commands

### Docker
```bash
docker-compose logs -f app      # View app logs
docker-compose restart app      # Restart app service
docker-compose down             # Stop all services
docker-compose down -v          # Complete reset (removes data)
docker-compose build --no-cache app  # Rebuild from scratch
```

### Application
```bash
docker-compose exec app bash                    # Access container shell
docker-compose exec app php artisan migrate     # Run migrations
docker-compose exec app php artisan tinker      # Laravel REPL
docker-compose exec app php artisan config:clear # Clear config cache
docker-compose exec app npm run build           # Rebuild frontend assets
```

### Supervisor (Process Management)
```bash
docker-compose exec app supervisorctl status                    # Check all processes
docker-compose exec app supervisorctl restart laravel-octane    # Restart Octane
docker-compose exec app supervisorctl restart laravel-queue:*   # Restart queue workers
docker-compose exec app supervisorctl restart laravel-scheduler # Restart scheduler
```

---

## 🏗️ Architecture

### Services

| Service | Container | Port | Description |
|---------|-----------|------|-------------|
| **App** | `restaurant_app` | 8000 | Laravel Octane (Swoole) |
| **MySQL** | `restaurant_mysql` | 3306 | Database |
| **Redis** | `restaurant_redis` | 6379 | Cache & Queue |
| **phpMyAdmin** | `restaurant_phpmyadmin` | 8080 | Database UI |
| **WhatsApp** | `restaurant_whatsapp` | 3001 | WhatsApp service |

### Managed Processes (Supervisor)

| Process | Description |
|---------|-------------|
| `laravel-octane` | Swoole HTTP server (port 80 internal → 8000 external) |
| `laravel-queue` | 2 queue workers for async jobs |
| `laravel-scheduler` | Laravel task scheduler |

---

## ⚙️ Configuration

### Environment Variables

Key variables in `.env`:

```env
APP_NAME=RestaurantReservation
APP_ENV=local
APP_DEBUG=true
APP_KEY=  # Auto-generated on first run

DB_HOST=mysql
DB_DATABASE=restaurant_reservation
DB_USERNAME=restaurant_user
DB_PASSWORD=your_secure_password
DB_PORT=3306

REDIS_HOST=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

**Note:** `DB_HOST` and `REDIS_HOST` are automatically set to the Docker service names at runtime.

---

## 🐛 Troubleshooting

### Container Won't Start
```bash
# Check logs for errors
docker-compose logs app

# Rebuild without cache
docker-compose build --no-cache app
docker-compose up -d

# Complete reset
docker-compose down -v
docker-compose up -d
```

### APP_KEY Errors
```bash
# The container auto-generates APP_KEY, but if issues persist:
docker-compose exec app php artisan key:generate --force
docker-compose exec app php artisan config:clear
docker-compose restart app
```

### Vite Manifest Not Found
```bash
# Rebuild frontend assets
docker-compose exec app npm install
docker-compose exec app npm run build
```

### Database Connection Failed
```bash
# Wait for MySQL to fully initialize (especially on first run)
docker-compose restart mysql
# Wait 30 seconds, then restart app
docker-compose restart app
```

### Queue Not Processing
```bash
docker-compose exec app supervisorctl status laravel-queue:*
docker-compose exec app supervisorctl restart laravel-queue:*
```

### Permission Issues
```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Fresh Start
```bash
# Remove all containers, volumes, and rebuild
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
docker-compose logs -f app
```

---

## 📚 API Endpoints

### Public API

**Check Availability**
```http
GET /api/v1/availability?date=2024-01-15&time=19:00&pax=4
```

**Create Reservation**
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

**Verify OTP**
```http
POST /api/v1/verify-otp
Content-Type: application/json

{
  "session_id": "uuid-here",
  "otp_code": "123456"
}
```

**Get Restaurant Settings**
```http
GET /api/v1/restaurant-settings
GET /api/v1/restaurant-settings?date=2024-01-15  # Date-specific settings
```

**Get Time Slots**
```http
GET /api/v1/time-slots
GET /api/v1/time-slots?date=2024-01-15  # Date-specific slots
```

---

## 🧪 Testing

### Unit Tests
```bash
docker-compose exec app php artisan test
```

---

## 📖 Documentation

- **Quick Start**: [`START_HERE.md`](START_HERE.md)
- **Contributing**: [`CONTRIBUTING.md`](CONTRIBUTING.md)

---

## 🎉 Credits

Built with:
- [Laravel 11](https://laravel.com) - PHP Framework
- [Laravel Octane](https://laravel.com/docs/octane) - High-performance server
- [Swoole](https://www.swoole.co.uk) - Async networking engine
- [Redis](https://redis.io) - Cache & Queue
- [Docker](https://docker.com) - Containerization
- [Supervisor](http://supervisord.org) - Process management
- [Vite](https://vitejs.dev) - Frontend build tool

---

**Made with ❤️ for restaurants everywhere**
