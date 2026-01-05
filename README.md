# Restaurant Reservation System

A high-performance restaurant reservation system built with Laravel 11, Redis, and Queue system. Designed to handle up to 1 million concurrent requests with robust security features.

## Features

### User Features
- Book tables with date and time selection
- Select number of guests (pax)
- Add optional notes (max 100 characters)
- Real-time availability checking
- Responsive booking interface

### Admin Features
- **Secure Authentication**: Login/logout with Laravel Sanctum
- **Protected Dashboard**: All admin routes require authentication
- View all reservations with filtering
- Cancel reservations
- Control reservation dates (open/close)
- Real-time system monitoring dashboard
- Rate limiting on login attempts

### Performance & Security
- Redis-based caching for high performance
- Queue system for handling high traffic
- Rate limiting (60 requests/minute per IP)
- Bot protection and spam prevention
- Redis locks to prevent race conditions
- Optimized database queries with indexes

## Requirements

- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Redis 6.0+
- Composer
- Shared hosting compatible

## Installation

### Option 1: Docker Setup (Recommended)

The easiest way to get started. See [`DOCKER_SETUP.md`](DOCKER_SETUP.md) for complete guide.

```bash
# Quick start with Docker
chmod +x docker-setup.sh
./docker-setup.sh

# Access application at http://localhost:8000
```

### Option 2: Manual Installation

1. **Clone or download the project**

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Update `.env` file with your database and Redis credentials:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=restaurant_reservation
   DB_USERNAME=your_username
   DB_PASSWORD=your_password

   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379

   QUEUE_CONNECTION=redis
   CACHE_STORE=redis
   SESSION_DRIVER=redis
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed database (creates admin user and sample tables)**
   ```bash
   php artisan db:seed
   ```
   
   **Default Admin Credentials:**
   - Email: `admin@restaurant.com`
   - Password: `password`
   
   ⚠️ **IMPORTANT**: Change the password after first login!

7. **Set up queue worker**
   ```bash
   php artisan queue:work redis --queue=reservations
   ```

   For production, use a process manager like Supervisor to keep the queue worker running.

## Configuration for High Traffic

### Queue Worker Setup (Supervisor)

Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --queue=reservations
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Redis Configuration

For high traffic, configure Redis with:
- Max memory policy: `allkeys-lru`
- Appropriate memory limits
- Persistence settings based on your needs

### PHP Configuration

Recommended PHP settings:
```ini
memory_limit=256M
max_execution_time=60
max_input_time=60
```

### Web Server Configuration

For Apache, ensure mod_rewrite is enabled. For Nginx, use appropriate configuration for Laravel.

## Usage

### Access Points

- **Booking Interface**: `http://your-domain.com/`
- **Admin Login**: `http://your-domain.com/login`
- **Admin Dashboard**: `http://your-domain.com/admin/reservations` (requires login)

### API Endpoints

- `GET /api/v1/availability?date=YYYY-MM-DD&time=HH:MM` - Check table availability
- `POST /api/v1/reservations` - Create a reservation

### API Request Example

```json
{
  "table_id": 1,
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "customer_phone": "+1234567890",
  "pax": 2,
  "reservation_date": "2024-12-25",
  "reservation_time": "19:00",
  "notes": "Window seat preferred"
}
```

## Security Features

1. **Authentication**: Laravel Sanctum for admin access
2. **Login Rate Limiting**: 5 attempts per minute per IP
3. **API Rate Limiting**: 60 requests per minute per IP
4. **Bot Protection**: Blocks known bot user agents
5. **Spam Prevention**: Maximum 3 reservations per hour per IP
6. **CSRF Protection**: All forms protected
7. **Input Validation**: Comprehensive validation on all inputs
8. **Redis Locks**: Prevents race conditions on concurrent bookings
9. **Session Security**: Secure session management with Redis
10. **Password Hashing**: Bcrypt password hashing

## Performance Optimizations

1. **Redis Caching**: Availability checks cached for 5 minutes
2. **Queue Processing**: All reservations processed asynchronously
3. **Database Indexes**: Optimized queries with proper indexes
4. **Connection Pooling**: Efficient database connections
5. **Lightweight Design**: Minimal dependencies for shared hosting

## Maintenance

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Monitor Queue
```bash
php artisan queue:monitor redis:reservations
```

### View Logs
```bash
tail -f storage/logs/laravel.log
```

## Troubleshooting

### Queue Not Processing
- Ensure Redis is running
- Check queue worker is running: `ps aux | grep queue:work`
- Verify queue connection in `.env`

### High Memory Usage
- Reduce number of queue workers
- Adjust cache TTL values
- Monitor Redis memory usage

### Slow Performance
- Check Redis connection
- Verify database indexes exist
- Monitor queue backlog
- Check server resources

## License

MIT License

## Support

For issues and questions, please check the logs in `storage/logs/laravel.log`.

