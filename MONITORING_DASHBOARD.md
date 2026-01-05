# System Monitoring Dashboard

## Overview

The System Monitoring Dashboard provides real-time insights into your restaurant reservation system's performance, resource usage, and visitor activity.

## Access

Navigate to: `/admin/monitoring`

Or click the "Monitoring" link in the admin navigation menu.

## Features

### 1. System Resources Monitoring

**CPU Usage**
- Real-time CPU utilization percentage
- Color-coded status indicators:
  - 🟢 Green: < 80% (Normal)
  - 🟡 Yellow: 80-90% (Warning)
  - 🔴 Red: > 90% (Critical)

**Memory Usage**
- Current memory consumption
- Total vs Used memory display
- Visual progress bar

**Disk Usage**
- Disk space utilization
- Available vs Used space
- Prevents storage issues

### 2. Queue System Monitoring

**Metrics Displayed:**
- **Pending Jobs**: Number of jobs waiting to be processed
- **Failed Jobs**: Total failed jobs in the system
- **Processed Today**: Jobs successfully processed today
- **Failed Today**: Jobs that failed today

**Status Indicators:**
- 🟢 Healthy: < 100 pending jobs
- 🟡 Warning: 100-500 pending jobs
- 🔴 Critical: > 500 pending jobs

### 3. Queue Workers Status

**Worker Information:**
- Active workers count
- Total workers configured
- Running status indicator

**Status:**
- 🟢 Running: Workers are active and processing jobs
- 🔴 Stopped: No workers running (action required!)

### 4. Visitor Tracking

**Real-time Visitor Metrics:**
- **Active Now**: Visitors currently on the site (last 5 minutes)
- **Last Minute**: Unique visitors in the last minute
- **Last Hour**: Unique visitors in the last hour
- **Today**: Total unique visitors today

**Tracking Features:**
- Uses Redis sets for efficient counting
- Falls back to cache if Redis unavailable
- Tracks unique IP addresses
- Updates in real-time

### 5. Database Monitoring

**Database Metrics:**
- **Connections**: Current database connections
- **Total Queries**: Cumulative query count

**Status Indicators:**
- 🟢 OK: Normal connection count
- 🟡 Warning: High connection count (>50)
- 🔴 Error: Database connection issues

### 6. Redis Monitoring

**Redis Metrics:**
- **Memory Usage**: Current Redis memory consumption
- **Max Memory**: Configured memory limit
- **Connected Clients**: Number of active Redis connections

**Status Indicators:**
- 🟢 OK: < 80% memory usage
- 🟡 Warning: 80-90% memory usage
- 🔴 Critical: > 90% memory usage

## Auto-Refresh

The dashboard automatically refreshes every **60 seconds** (1 minute) to provide real-time updates without manual page refresh.

**Features:**
- Automatic updates every minute
- Last update timestamp displayed
- Smooth transitions without page reload
- Error handling with user-friendly messages

## Performance Optimizations

### Caching Strategy
- CPU usage cached for 30 seconds
- Visitor counts cached with appropriate TTLs
- System metrics optimized for minimal overhead

### Efficient Tracking
- Uses Redis sets for O(1) visitor counting
- Fallback to cache-based tracking if Redis unavailable
- Memory-efficient IP tracking

### Error Handling
- Graceful degradation if services unavailable
- User-friendly error messages
- Continues monitoring other metrics if one fails

## Troubleshooting

### No Data Showing
1. Check Redis connection: `redis-cli ping`
2. Verify queue worker is running
3. Check `storage/logs/laravel.log` for errors

### CPU Usage Not Available
- On Windows: Requires `wmic` command
- On Linux: Requires `/proc/stat` access
- May show 0% if system commands unavailable

### Worker Status Shows Stopped
1. Start queue worker: `php artisan queue:work redis --queue=reservations`
2. Check Supervisor/systemd configuration
3. Verify Redis connection

### Visitor Counts Not Updating
1. Verify VisitorTrackingMiddleware is active
2. Check Redis connection
3. Ensure middleware is registered in `bootstrap/app.php`

## Daily Metrics Reset

Daily metrics (processed/failed jobs) are automatically reset at midnight via scheduled command:
```bash
php artisan metrics:reset-daily
```

This is configured in `app/Console/Kernel.php` to run daily.

## API Endpoint

The dashboard uses the following API endpoint:
- `GET /admin/monitoring/metrics` - Returns JSON with all metrics

**Response Format:**
```json
{
  "success": true,
  "data": {
    "system": { ... },
    "queue": { ... },
    "workers": { ... },
    "visitors": { ... },
    "database": { ... },
    "redis": { ... },
    "timestamp": "2024-01-01T12:00:00Z"
  }
}
```

## Best Practices

1. **Monitor Regularly**: Check dashboard daily for anomalies
2. **Set Alerts**: Configure alerts for critical thresholds
3. **Worker Monitoring**: Ensure workers are always running
4. **Resource Planning**: Use metrics to plan capacity
5. **Performance Tuning**: Use metrics to optimize system

## Security

- Dashboard is accessible via admin routes
- No sensitive data exposed
- Metrics are read-only
- Rate limiting applies to API endpoint

## Future Enhancements

Potential additions:
- Historical charts and graphs
- Email/SMS alerts for critical issues
- Export metrics to CSV/JSON
- Custom alert thresholds
- Performance trend analysis

