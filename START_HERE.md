# 🎉 Quick Start Guide

> Get your restaurant reservation system running in **2 minutes**!

---

## ⚡ Fast Setup

### 1. Start Docker

```bash
docker-compose build
docker-compose up -d
```

### 2. Access Application

Open your browser:
- **Booking**: http://localhost:8000
- **Admin Panel** 🎨: http://localhost:8000/admin 
- **Database**: http://localhost:8080 (phpMyAdmin)

**Login**: `admin@restaurant.com` / `password`  
**Database**: `restaurant_user` / `restaurant_password`

**That's it!** 🎊

> 🎨 **New Feature**: The admin panel now uses Laravel Filament - A modern, professional UI with dark mode, advanced search, filtering, and export features!

---

## 📖 What's Running?

All services started automatically:
- ✅ Nginx web server
- ✅ PHP 8.4 application
- ✅ MySQL database (with tables & admin user)
- ✅ Redis cache
- ✅ 2 Queue workers
- ✅ Scheduler (cron jobs)
- ✅ phpMyAdmin (database management)

---

## 🎯 Quick Actions

### View Logs
```bash
docker-compose logs -f
```

### Restart Everything
```bash
docker-compose restart
```

### Stop Services
```bash
docker-compose stop
```

### Start Again
```bash
docker-compose start
```

### Complete Reset
```bash
docker-compose down -v
docker-compose up -d
```

---

## 🔑 Important First Steps

1. **Explore Filament Admin**
   - Login at http://localhost:8000/admin
   - Check out the modern UI and features
   - Try dark mode toggle (top right)
   - Browse reservations, tables, settings

2. **Change Admin Password**
   - Click on your profile (top right)
   - Update your password
   - ⚠️ Important for security!

3. **Test Booking**
   - Visit http://localhost:8000
   - Make a test reservation
   - Check admin panel for confirmation

4. **Check Monitoring**
   - Go to http://localhost:8000/admin/monitoring
   - Verify all metrics are working

---

## 🐛 Something Wrong?

### Site Not Loading?
```bash
docker-compose ps          # Check if running
docker-compose logs app    # Check for errors
docker-compose restart     # Restart everything
```

### Database Error?
```bash
# Wait 30 seconds for MySQL to initialize
docker-compose restart app
```

### Need Fresh Start?
```bash
docker-compose down -v
docker-compose build
docker-compose up -d
```

---

## 📚 Learn More

- **Full Documentation**: See [`README.md`](README.md)
- **Production Deployment**: See [`DEPLOYMENT.md`](DEPLOYMENT.md)
- **All Commands**: Run `docker-compose exec app php artisan`

---

## 🎓 Key Features

- 🎫 **User Bookings** - Easy reservation form
- 📊 **Admin Dashboard** - Manage all bookings
- 📈 **Real-time Monitoring** - System health metrics
- ⚡ **High Performance** - Redis + Queue system
- 🔒 **Secure** - Rate limiting, bot protection, CSRF
- 🐳 **Docker** - Everything containerized

---

## 💡 Pro Tips

1. Monitor dashboard auto-refreshes every minute
2. Queue jobs process asynchronously
3. All data persists in Docker volumes
4. Configuration cached for performance
5. Workers auto-restart on failure

---

## 📞 Need Help?

Check the full [`README.md`](README.md) for:
- Detailed architecture
- Configuration options
- API documentation
- Troubleshooting guide
- Production deployment

---

**Happy Booking! 🍽️**
