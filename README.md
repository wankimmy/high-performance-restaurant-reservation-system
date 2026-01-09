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
- 📊 System monitoring dashboard

### Performance
- ⚡ Dual server support (Nginx/PHP-FPM or Swoole/Octane)
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
cd HPBS

# 2. Build and start
docker-compose build
docker-compose up -d
```

**That's it!** The container automatically sets up the database, seeds sample data, and starts all services.

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
docker-compose logs -f          # View logs
docker-compose restart          # Restart services
docker-compose down -v          # Complete reset
```

### Application
```bash
docker-compose exec app bash                    # Access container
docker-compose exec app php artisan migrate     # Run migrations
docker-compose exec app php artisan tinker       # Laravel REPL
```

---

## ⚙️ Configuration

### Environment Variables

Key variables in `.env`:

```env
APP_NAME=RestaurantReservation
APP_ENV=production
APP_DEBUG=false

DB_HOST=mysql
DB_DATABASE=restaurant_reservation
DB_USERNAME=restaurant_user
DB_PASSWORD=your_secure_password

REDIS_HOST=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis

SERVER_TYPE=nginx  # or 'swoole' for high performance
```

### Switch to Swoole/Octane

Edit `docker-compose.yml`:
```yaml
environment:
  - SERVER_TYPE=swoole
```

Then rebuild:
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

---

## 🐛 Troubleshooting

### Container Won't Start
```bash
docker-compose logs app
docker-compose build --no-cache
docker-compose down -v && docker-compose up -d
```

### Database Connection Failed
```bash
docker-compose restart mysql
# Wait 30 seconds for initialization
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

### Load Testing
```bash
# Build stress test container
docker-compose build stress-test

# Run load test
docker-compose run --rm stress-test wrk -t4 -c50 -d30s --latency http://app:80/
```

See [`STRESS_TEST.md`](STRESS_TEST.md) for complete guide.

---

## 🚀 Production Deployment

### Pre-deployment Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Generate new `APP_KEY`
- [ ] Use strong database passwords
- [ ] Change default admin password
- [ ] Set up SSL/TLS certificate
- [ ] Configure firewall rules
- [ ] Set up database backups
- [ ] Remove or restrict phpMyAdmin access

See [`DEPLOYMENT.md`](DEPLOYMENT.md) for detailed guide.

---

## 📖 Documentation

- **Quick Start**: [`START_HERE.md`](START_HERE.md)
- **Deployment**: [`DEPLOYMENT.md`](DEPLOYMENT.md)
- **Stress Testing**: [`STRESS_TEST.md`](STRESS_TEST.md)
- **Contributing**: [`CONTRIBUTING.md`](CONTRIBUTING.md)

---

## 🎉 Credits

Built with:
- [Laravel 11](https://laravel.com) - PHP Framework
- [Laravel Octane](https://laravel.com/docs/octane) - High-performance server
- [Swoole](https://www.swoole.co.uk) - Networking engine
- [Redis](https://redis.io) - Cache & Queue
- [Docker](https://docker.com) - Containerization

---

**Made with ❤️ for restaurants everywhere**
