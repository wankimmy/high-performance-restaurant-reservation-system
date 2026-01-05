# Quick Start Guide

## Option 1: Docker Setup (Recommended)

```bash
# Run automated setup
chmod +x docker-setup.sh
./docker-setup.sh

# Access application
# Booking: http://localhost:8000
# Admin: http://localhost:8000/login
```

See [`DOCKER_SETUP.md`](DOCKER_SETUP.md) for complete Docker documentation.

## Option 2: Manual Installation

### Installation Steps

1. **Install Composer Dependencies**
   ```bash
   composer install
   ```

2. **Set Up Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure Database & Redis**
   Edit `.env` file with your credentials:
   ```env
   DB_DATABASE=restaurant_reservation
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   ```

4. **Run Migrations**
   ```bash
   php artisan migrate
   ```

5. **Seed Sample Tables (Optional)**
   ```bash
   php artisan db:seed
   ```

6. **Start Queue Worker**
   ```bash
   php artisan queue:work redis --queue=reservations
   ```

7. **Access the Application**
   - Booking: http://localhost/
   - Admin Login: http://localhost/login
   - Admin Dashboard: http://localhost/admin/reservations (requires login)

## Admin Login

**Default Credentials:**
- Email: `admin@restaurant.com`
- Password: `password`

⚠️ **Change the password after first login!**

## Testing the System

### Create a Reservation
1. Go to the booking page
2. Select a date and time
3. Click "Check Availability"
4. Select a table
5. Fill in customer details
6. Submit the reservation

### Admin Functions
1. Go to `/admin/reservations` to view all reservations
2. Click "Cancel" to cancel a reservation
3. Go to `/admin/settings` to control reservation dates

## Production Deployment

See `DEPLOYMENT.md` for detailed deployment instructions.

## Key Features

✅ High-performance queue system  
✅ Redis caching  
✅ Rate limiting (60 req/min)  
✅ Bot protection  
✅ Spam prevention (3 reservations/hour/IP)  
✅ Race condition prevention with Redis locks  
✅ Optimized for 1M+ concurrent requests  

## Troubleshooting

**Queue not processing?**
- Check Redis is running: `redis-cli ping`
- Verify queue worker is running
- Check `.env` QUEUE_CONNECTION=redis

**500 Error?**
- Check `storage/logs/laravel.log`
- Verify file permissions: `chmod -R 775 storage bootstrap/cache`
- Ensure `.env` is configured correctly

**Slow performance?**
- Enable OPcache
- Check Redis memory usage
- Monitor queue backlog

