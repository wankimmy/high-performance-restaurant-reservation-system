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
- ⏰ **Dynamic Time Slots** - Automatically generated time slots based on restaurant opening hours and configured interval
- 👥 **Party Size** - Specify number of guests (pax)
- 💰 **Deposit Display** - Real-time deposit calculation based on number of guests and configured deposit per person
- 🪑 **Table Selection** - Visual grid of available tables with capacity matching
- 📝 **Special Requests** - Add notes (up to 100 characters)
- ✅ **Real-time Availability** - Check table status instantly based on date, time, and pax
- 🔐 **OTP Verification** - WhatsApp OTP verification for secure bookings
- 🔄 **Queue Processing** - Real-time reservation processing with queue number
- 🔒 **Secure Booking** - CSRF protection and validation

### Admin Dashboard
- 🎨 **Modern UI** - Professional, clean interface with Tailwind CSS
- 📋 **Reservation Management** - View, edit, and cancel bookings with DataTables
- 👤 **Customer Information** - Display customer name, phone, email, notes, and deposit in separate columns
- ✅ **Arrival Verification** - Two-step OTP verification: Send OTP to customer, then admin verifies the code shown by customer
- 🗓️ **Date Control** - Open/close specific reservation dates
- ⚙️ **Restaurant Settings** - Configure opening hours, closing hours, deposit per person, and time slot intervals
- ⏰ **Time Slot Management** - Automatic time slot generation based on opening hours and interval (15, 30, 45, 60, 90, or 120 minutes)
- 💰 **Deposit Management** - Set deposit amount per person, automatically calculated and displayed during booking
- 📱 **WhatsApp Integration** - WhatsApp Web integration using Baileys for sending OTP and notifications
- 🪑 **Table Management** - Control table availability with DataTables search
- 📊 **Real-time Monitoring** - System health and metrics dashboard
- ⚡ **Laravel Pulse** - Real-time application performance monitoring
- 📱 **Mobile Responsive** - Full admin functionality on any device
- 🔍 **Advanced Search** - DataTables with search across all columns
- 🔐 **Secure Authentication** - Laravel Breeze authentication

### Performance & Scalability
- ⚡ **High Traffic Support** - Handles up to 1M concurrent requests
- 🚀 **Dual Server Support** - Switch between Nginx/PHP-FPM or Swoole/Octane
- 🔄 **Laravel Octane** - High-performance application server with Swoole
- 💨 **Redis Caching** - Lightning-fast data access
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
- ⚡ **Laravel Pulse** - Real-time application performance monitoring with detailed metrics

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
- ✅ Installs Laravel Octane and Pulse
- ✅ Optimizes configuration
- ✅ Starts all services (Nginx/PHP-FPM or Swoole/Octane, Queue Workers, Scheduler, WhatsApp Service)

### Access the Application

| Service | URL | Credentials |
|---------|-----|-------------|
| **Booking Page** | http://localhost:8000 | (Public access) |
| **Admin Panel** 🎨 | http://localhost:8000/admin/dashboard | admin@restaurant.com / password |
| **Reservations** | http://localhost:8000/admin/reservations | (After login) |
| **Restaurant Settings** ⚙️ | http://localhost:8000/admin/restaurant-settings | (After login) |
| **WhatsApp Settings** 📱 | http://localhost:8000/admin/whatsapp-settings | (After login) |
| **Date Settings** | http://localhost:8000/admin/settings | (After login) |
| **Tables** | http://localhost:8000/admin/tables | (After login) |
| **Monitoring** | http://localhost:8000/admin/monitoring | (After login) |
| **Laravel Pulse** ⚡ | http://localhost:8000/pulse | (After login) |
| **phpMyAdmin** | http://localhost:8080 | restaurant_user / restaurant_password |
| **WhatsApp Service** 📱 | http://localhost:3001/health | (Health check) |
| **Stress Test** 🧪 | `docker-compose run --rm stress-test` | (See STRESS_TEST.md) |

**🎨 Features**: 
- Admin panel with DataTables for advanced search and filtering
- Laravel Pulse for real-time performance monitoring
- Restaurant settings: Configure opening hours, closing hours, deposit per person, and time slot intervals
- WhatsApp integration: Connect WhatsApp Web via QR code, send OTP and notifications automatically
- Dynamic time slot generation based on restaurant hours
- Two-step arrival verification: Admin sends OTP to customer, then verifies the code shown by customer
- Deposit calculation and display during booking
- Switchable server: Nginx (default) or Swoole/Octane for high performance

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
│     OR (if SERVER_TYPE=swoole)      │
│     ┌──────────┐  ┌──▼─┐  ┌──▼─┐    │
│     │  Octane  │  │Queue│ │Cron│    │
│     │ (Swoole) │  │  x2 │ │Job │    │
│     └──────────┘  └────┘  └────┘    │
└──────────────────────────────────────┘
         │              │              │
    ┌────▼────┐    ┌───▼─────┐    ┌───▼──────────┐
    │ MySQL   │    │  Redis  │    │  WhatsApp    │
    │  :3306  │    │  :6379  │    │  Service     │
    └─────────┘    └─────────┘    │  (Baileys)   │
                                   │  :3001       │
                                   └──────────────┘
```

### Service Stack
- **Web Server**: Nginx (default) or Laravel Octane with Swoole
- **Application**: PHP 8.4-FPM (with Nginx) or PHP 8.4 with Swoole (with Octane)
- **Database**: MySQL 8.0
- **Cache/Queue**: Redis
- **WhatsApp Service**: Node.js service with Baileys for WhatsApp Web integration
- **Queue Workers**: 2 parallel processes (auto-started via Supervisor)
- **Scheduler**: Laravel schedule:work (auto-started via Supervisor)
- **Process Manager**: Supervisor (manages all services)
- **Performance Monitoring**: Laravel Pulse

---

## 🔧 Common Commands

### Docker Management

```bash
# View all logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app
docker-compose logs -f whatsapp-service

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

# Restart WhatsApp service
docker-compose restart whatsapp-service
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
docker-compose exec app supervisorctl restart nginx  # If using Nginx
docker-compose exec app supervisorctl restart laravel-octane  # If using Swoole
docker-compose exec app supervisorctl restart laravel-queue:*

# View service logs
docker-compose exec app supervisorctl tail -f nginx  # If using Nginx
docker-compose exec app supervisorctl tail -f laravel-octane  # If using Swoole
docker-compose exec app supervisorctl tail -f laravel-queue
```

---

## 🔍 Monitoring

### Access the Dashboards
- **Custom Monitoring**: http://localhost:8000/admin/monitoring (requires login)
- **Laravel Pulse**: http://localhost:8000/pulse (requires login)

### Custom Monitoring Dashboard Metrics
- **System**: CPU, Memory, Disk usage
- **Queue**: Jobs pending, processed, failed
- **Workers**: Status of queue workers
- **Visitors**: Real-time active users
- **Database**: Connection pool, query count
- **Redis**: Memory usage, connected clients

### Laravel Pulse Features
- ⚡ **Real-time Performance Metrics** - Request times, throughput, memory usage
- 📊 **Slow Queries** - Identify and optimize slow database queries
- 🔥 **Slow Requests** - Track slow HTTP requests
- 📈 **Jobs** - Monitor queue job performance
- 👥 **Users** - Track active users and requests
- 💾 **Cache** - Monitor cache hit rates
- 🔴 **Exceptions** - Track application errors
- 📱 **Servers** - Monitor multiple server instances

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

# Server Type (nginx or swoole)
# Use 'swoole' for high-performance with Laravel Octane
# Use 'nginx' for traditional Nginx + PHP-FPM setup
SERVER_TYPE=nginx
```

### Customization

#### Switch to Swoole/Octane for High Performance
Edit `docker-compose.yml` or set in `.env`:
```yaml
environment:
  - SERVER_TYPE=swoole  # Use Swoole instead of Nginx
```
Then rebuild and restart:
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

**Benefits of Swoole/Octane:**
- ⚡ Higher performance and throughput
- 🔄 Persistent application state
- 💨 Lower latency
- 🚀 Better for high-traffic scenarios

**Note**: When using Swoole, Nginx and PHP-FPM are automatically disabled.

#### Change Number of Queue Workers
Edit `docker/supervisor/supervisord.conf`:
```ini
[program:laravel-queue]
numprocs=4  # Change from 2 to 4
```

#### Adjust Octane Workers (if using Swoole)
Edit `docker/supervisor/supervisord.conf`:
```ini
[program:laravel-octane]
command=php /var/www/html/artisan octane:start --server=swoole --host=0.0.0.0 --port=80 --workers=8 --task-workers=4
```

#### Adjust Rate Limiting
Edit `app/Http/Middleware/RateLimitMiddleware.php`:
```php
$maxAttempts = 100;  // Change from 60
```

#### Configure Restaurant Settings
Access the Restaurant Settings page at `/admin/restaurant-settings` to configure:
- **Opening Hours**: Restaurant opening time (e.g., 09:00)
- **Closing Hours**: Restaurant closing time (e.g., 22:00)
- **Time Slot Interval**: Interval between booking slots (15, 30, 45, 60, 90, or 120 minutes)
- **Deposit Per Person**: Deposit amount charged per guest (e.g., RM 10.00)

Time slots are automatically generated based on these settings. For example:
- Opening: 09:00, Closing: 22:00, Interval: 30 minutes
- Generates: 9:00 AM, 9:30 AM, 10:00 AM, ... 9:30 PM

#### Configure WhatsApp Integration
Access the WhatsApp Settings page at `/admin/whatsapp-settings` to:
1. **Enable WhatsApp**: Toggle WhatsApp messaging on/off
2. **Connect WhatsApp**: Click "Connect WhatsApp" to generate QR code
3. **Scan QR Code**: Open WhatsApp on your phone → Settings → Linked Devices → Link a Device → Scan QR Code
4. **Service URL**: Configure WhatsApp service URL (default: `http://whatsapp-service:3001`)

Once connected, the system will automatically:
- Send OTP codes to customers via WhatsApp during booking
- Send reservation confirmations via WhatsApp
- Send arrival verification OTPs when admin requests verification

**Note**: The WhatsApp service runs in a separate Docker container and persists authentication state, so you only need to scan the QR code once.

#### Modify Table Capacity
```bash
docker-compose exec app php artisan tinker
> \App\Models\Table::create(['name' => 'VIP Table', 'capacity' => 8, 'is_available' => true]);
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

### Unit & Feature Tests

```bash
# PHPUnit tests
docker-compose exec app php artisan test

# Specific test file
docker-compose exec app php artisan test --filter ReservationTest

# With coverage
docker-compose exec app php artisan test --coverage
```

### Load & Stress Testing

A dedicated stress test container is available with multiple load testing tools.

#### Quick Start

```bash
# Build the stress test container
docker-compose build stress-test

# Run a quick load test (50 concurrent users, 30 seconds)
docker-compose run --rm stress-test wrk -t4 -c50 -d30s --latency http://app:80/

# Run pre-configured test suite
docker-compose run --rm stress-test ./run-full-test.sh http://app:80 60s 100
```

#### Available Tools

- **wrk** - High-performance HTTP benchmarking
- **Apache Bench (ab)** - Simple HTTP benchmarking
- **hey** - Modern HTTP load testing tool

#### Test Scenarios

```bash
# Light load (10 concurrent users)
docker-compose run --rm stress-test wrk -t2 -c10 -d10s --latency http://app:80/

# Medium load (50 concurrent users)
docker-compose run --rm stress-test wrk -t4 -c50 -d30s --latency http://app:80/

# Heavy load (200 concurrent users)
docker-compose run --rm stress-test wrk -t8 -c200 -d60s --latency http://app:80/
```

#### Compare Server Performance

```bash
# Test with Nginx
docker-compose up -d app
docker-compose run --rm stress-test ./run-full-test.sh http://app:80 60s 100

# Test with Swoole/Octane
SERVER_TYPE=swoole docker-compose up -d app
docker-compose run --rm stress-test ./run-full-test.sh http://app:80 60s 100
```

See [`STRESS_TEST.md`](STRESS_TEST.md) for complete stress testing guide.

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

#### Get Restaurant Settings
```http
GET /api/v1/restaurant-settings
```

Returns:
```json
{
  "success": true,
  "settings": {
    "opening_time": "09:00",
    "closing_time": "22:00",
    "deposit_per_pax": 10.00,
    "time_slot_interval": 30
  }
}
```

#### Get Time Slots
```http
GET /api/v1/time-slots
```

Returns dynamically generated time slots based on restaurant settings:
```json
{
  "success": true,
  "time_slots": [
    {
      "start_time": "09:00",
      "end_time": "09:30",
      "display": "9:00 AM",
      "value": "09:00"
    },
    {
      "start_time": "09:30",
      "end_time": "10:00",
      "display": "9:30 AM",
      "value": "09:30"
    }
  ]
}
```

### Booking Flow
1. User selects date, time (from dynamically generated slots), pax, and table from visual grid
2. Deposit amount automatically calculated and displayed based on number of guests
3. User submits booking form → Redirects to `/verify-otp`
4. System sends OTP to user's WhatsApp via Baileys service
5. User enters OTP sent via WhatsApp
6. After verification → Redirects to `/queue` (shows queue number)
7. Queue page polls for status → Redirects to result page
8. Reservation confirmation sent to user's WhatsApp

### Arrival Verification Flow
1. Admin views reservation in admin panel
2. Admin clicks "Verify Attendance" button
3. System sends OTP to customer's WhatsApp via Baileys service
4. Customer arrives and shows OTP code to admin
5. Admin enters OTP in verification modal
6. System verifies OTP and marks customer as arrived

### Admin Features

#### Reservation Management
- **DataTables Integration**: Advanced search, sorting, and pagination across all columns
- **Customer Information**: Separate columns for name, phone, email, notes, and deposit amount
- **Arrival Verification**: Two-step OTP verification process:
  1. Admin clicks "Verify Attendance" → OTP sent to customer's WhatsApp
  2. Customer shows OTP to admin
  3. Admin enters OTP in verification modal → Customer marked as arrived
- **Status Management**: View and manage reservation status (pending, confirmed, cancelled)
- **Date Filtering**: Filter reservations by date and status

#### Restaurant Settings
- **Opening Hours**: Configure restaurant opening time (e.g., 09:00)
- **Closing Hours**: Configure restaurant closing time (e.g., 22:00)
- **Time Slot Interval**: Set interval between booking slots (15, 30, 45, 60, 90, or 120 minutes)
- **Deposit Per Person**: Configure deposit amount charged per guest
- **Live Preview**: See generated time slots preview based on current settings
- **Automatic Generation**: Time slots automatically generated from opening hours and interval

#### WhatsApp Settings
- **Connection Management**: Connect/disconnect WhatsApp Web via QR code scanning
- **Status Monitoring**: Real-time connection status and QR code display
- **Service Configuration**: Configure WhatsApp Baileys service URL
- **Auto-reconnect**: Service automatically reconnects on container restart if previously authenticated
- **Message Types**: Automatically sends OTP codes, reservation confirmations, and arrival verification OTPs

#### Table Management
- **DataTables Integration**: Search and filter tables by name, capacity, and availability
- **Availability Control**: Manually toggle table availability
- **Capacity Management**: Set and view table capacity
- **Reservation Tracking**: See upcoming reservations for each table

#### Dashboard Analytics
- **Available Tables**: Calculated based on actual bookings for the day
- **Today's Reservations**: Count of reservations for today
- **Total Statistics**: Overview of all reservations and tables

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
- **Stress Testing**: [`STRESS_TEST.md`](STRESS_TEST.md) - Load testing guide
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
- [Laravel Octane](https://laravel.com/docs/octane) - High-performance application server
- [Laravel Pulse](https://laravel.com/docs/pulse) - Real-time application monitoring
- [Swoole](https://www.swoole.co.uk) - High-performance coroutine-based networking engine
- [Bootstrap 5](https://getbootstrap.com) - Frontend framework
- [Bootstrap Datepicker](https://github.com/uxsolutions/bootstrap-datepicker) - Date selection
- [DataTables](https://datatables.net) - Advanced table features with search and sorting
- [Baileys](https://github.com/WhiskeySockets/Baileys) - WhatsApp Web API library
- [Redis](https://redis.io) - In-memory data store
- [MySQL](https://mysql.com) - Database
- [Nginx](https://nginx.org) - Web server (optional, can use Swoole instead)
- [Docker](https://docker.com) - Containerization
- [Supervisor](http://supervisord.org) - Process management

---

**Made with ❤️ for restaurants everywhere**
