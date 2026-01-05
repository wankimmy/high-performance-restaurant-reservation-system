# Project Structure

## Directory Overview

```
HPBS/
├── app/
│   ├── Console/
│   │   └── Kernel.php                    # Scheduled tasks
│   ├── Exceptions/
│   │   └── Handler.php                   # Exception handling
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── ReservationController.php  # API endpoints
│   │   │   ├── Admin/
│   │   │   │   └── AdminReservationController.php  # Admin dashboard
│   │   │   └── Controller.php            # Base controller
│   │   └── Middleware/
│   │       ├── RateLimitMiddleware.php   # Rate limiting
│   │       ├── BotProtectionMiddleware.php # Bot blocking
│   │       ├── SpamProtectionMiddleware.php # Spam prevention
│   │       ├── TrustProxies.php
│   │       ├── EncryptCookies.php
│   │       ├── VerifyCsrfToken.php
│   │       └── ... (other Laravel middleware)
│   ├── Jobs/
│   │   └── ProcessReservation.php        # Queue job for reservations
│   ├── Models/
│   │   ├── Table.php                     # Table model
│   │   ├── Reservation.php              # Reservation model
│   │   └── ReservationSetting.php      # Settings model
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── RouteServiceProvider.php
├── bootstrap/
│   ├── app.php                          # Application bootstrap
│   └── cache/                           # Cached files
├── config/
│   ├── app.php                          # Application config
│   ├── cache.php                        # Cache config (Redis)
│   ├── database.php                     # Database config
│   ├── queue.php                        # Queue config (Redis)
│   ├── session.php                      # Session config (Redis)
│   └── logging.php                      # Logging config
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_tables.php
│   │   ├── 2024_01_01_000002_create_reservations.php
│   │   ├── 2024_01_01_000003_create_reservation_settings.php
│   │   └── 2024_01_01_000004_create_rate_limits.php
│   └── seeders/
│       └── DatabaseSeeder.php          # Sample tables seeder
├── public/
│   ├── index.php                        # Entry point
│   └── .htaccess                       # Apache configuration
├── resources/
│   └── views/
│       ├── booking/
│       │   └── index.blade.php         # Booking interface
│       └── admin/
│           ├── reservations/
│           │   └── index.blade.php    # Reservations list
│           └── settings/
│               └── index.blade.php    # Settings management
├── routes/
│   ├── api.php                         # API routes
│   ├── web.php                         # Web routes
│   └── console.php                     # Console routes
├── storage/
│   ├── framework/                      # Framework files
│   └── logs/                           # Application logs
├── .env.example                        # Environment template
├── .gitignore                          # Git ignore rules
├── artisan                             # Artisan CLI
├── composer.json                       # PHP dependencies
├── README.md                           # Main documentation
├── DEPLOYMENT.md                       # Deployment guide
├── QUICK_START.md                      # Quick start guide
└── PROJECT_STRUCTURE.md                # This file
```

## Key Components

### Models
- **Table**: Restaurant tables with capacity
- **Reservation**: Customer reservations
- **ReservationSetting**: Date-based reservation controls

### Controllers
- **ReservationController**: API for booking (with queue processing)
- **AdminReservationController**: Admin dashboard management

### Middleware
- **RateLimitMiddleware**: 60 requests/minute per IP
- **BotProtectionMiddleware**: Blocks known bots
- **SpamProtectionMiddleware**: 3 reservations/hour per IP

### Jobs
- **ProcessReservation**: Async reservation processing via Redis queue

### Database Tables
1. **tables**: Restaurant tables
2. **reservations**: Customer bookings
3. **reservation_settings**: Date availability controls
4. **rate_limits**: Rate limiting tracking

## API Endpoints

### Public API
- `GET /api/v1/availability?date=YYYY-MM-DD&time=HH:MM` - Check availability
- `POST /api/v1/reservations` - Create reservation

### Admin Routes
- `GET /admin/reservations` - View reservations
- `POST /admin/reservations/{id}/cancel` - Cancel reservation
- `GET /admin/settings` - View settings
- `POST /admin/settings/toggle` - Toggle date availability

## Performance Features

1. **Redis Caching**: Availability checks cached for 5 minutes
2. **Queue System**: All reservations processed asynchronously
3. **Database Indexes**: Optimized queries
4. **Redis Locks**: Prevents race conditions
5. **Rate Limiting**: Prevents abuse

## Security Features

1. **CSRF Protection**: All forms protected
2. **Input Validation**: Comprehensive validation
3. **Bot Blocking**: User agent filtering
4. **Spam Prevention**: IP-based limits
5. **Rate Limiting**: Request throttling

