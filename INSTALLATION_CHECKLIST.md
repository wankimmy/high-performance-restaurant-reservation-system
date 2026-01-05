# Installation Checklist

Use this checklist to verify your installation is complete and working correctly.

## Pre-Installation

- [ ] PHP 8.2 or higher installed
- [ ] MySQL/MariaDB database created
- [ ] Redis server installed and running
- [ ] Composer installed
- [ ] Web server (Apache/Nginx) configured

## Installation Steps

- [ ] Run `composer install`
- [ ] Copy `.env.example` to `.env`
- [ ] Generate application key: `php artisan key:generate`
- [ ] Configure database credentials in `.env`
- [ ] Configure Redis credentials in `.env`
- [ ] Run migrations: `php artisan migrate`
- [ ] (Optional) Seed sample data: `php artisan db:seed`
- [ ] Set file permissions: `chmod -R 775 storage bootstrap/cache`

## Configuration Verification

- [ ] Database connection working: `php artisan tinker` then `DB::connection()->getPdo();`
- [ ] Redis connection working: `redis-cli ping` should return `PONG`
- [ ] Queue connection set to `redis` in `.env`
- [ ] Cache store set to `redis` in `.env`
- [ ] Session driver set to `redis` in `.env`

## Queue Worker Setup

- [ ] Queue worker started: `php artisan queue:work redis --queue=reservations`
- [ ] Queue worker running continuously (use Supervisor for production)
- [ ] Queue processes jobs successfully

## Functionality Tests

### Booking System
- [ ] Can access booking page at `/`
- [ ] Can select date and time
- [ ] "Check Availability" button works
- [ ] Available tables are displayed
- [ ] Can select a table
- [ ] Can fill in customer details
- [ ] Can add notes (max 100 characters)
- [ ] Can submit reservation
- [ ] Reservation appears in admin dashboard

### Admin Dashboard
- [ ] Can access admin reservations at `/admin/reservations`
- [ ] Can view list of reservations
- [ ] Can filter by status
- [ ] Can filter by date
- [ ] Can cancel a reservation
- [ ] Can access settings at `/admin/settings`
- [ ] Can toggle date availability
- [ ] Settings are saved correctly

### Security Features
- [ ] Rate limiting works (test with multiple rapid requests)
- [ ] Bot protection blocks suspicious user agents
- [ ] Spam protection limits reservations per IP
- [ ] CSRF protection active on forms
- [ ] Input validation working

### Performance
- [ ] Availability checks are cached
- [ ] Queue processes reservations asynchronously
- [ ] Redis locks prevent race conditions
- [ ] Database queries are optimized

## Production Readiness

- [ ] `APP_DEBUG=false` in `.env`
- [ ] `APP_ENV=production` in `.env`
- [ ] Optimized: `php artisan config:cache`
- [ ] Routes cached: `php artisan route:cache`
- [ ] Views cached: `php artisan view:cache`
- [ ] Queue worker running via Supervisor/systemd
- [ ] Logs directory writable
- [ ] SSL certificate installed (HTTPS)
- [ ] Backup strategy in place

## Troubleshooting

If any item fails:

1. **Database Issues**: Check `.env` DB credentials, verify database exists
2. **Redis Issues**: Verify Redis is running, check connection in `.env`
3. **Queue Issues**: Ensure queue worker is running, check Redis connection
4. **Permission Issues**: Run `chmod -R 775 storage bootstrap/cache`
5. **500 Errors**: Check `storage/logs/laravel.log` for details
6. **Route Issues**: Clear cache: `php artisan route:clear`

## Performance Monitoring

Monitor these metrics:
- Queue backlog size
- Redis memory usage
- Database query performance
- Response times
- Error rates in logs

## Support

If issues persist:
1. Check `storage/logs/laravel.log`
2. Verify all checklist items
3. Review `DEPLOYMENT.md` for detailed instructions
4. Check server resources (CPU, memory, disk)

