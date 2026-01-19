# k6 Stress Testing - Maximum Performance Tests

This directory contains k6 stress testing scripts designed to push the restaurant reservation application to its maximum capacity and find the highest RPS (Requests Per Second) it can handle.

## Prerequisites

Install k6 on your system:

### Windows
```powershell
# Using Chocolatey
choco install k6

# Or download from https://k6.io/docs/getting-started/installation/
```

### macOS
```bash
brew install k6
```

### Linux
```bash
# Debian/Ubuntu
sudo gpg -k
sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D6
echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update
sudo apt-get install k6
```

## Quick Start

### Run Stress Test

```bash
# Stress test all API endpoints (includes complete booking flow)
k6 run stress-test.js
```

### Custom Base URL

```bash
# Test against a different URL
k6 run -e BASE_URL=http://localhost:8000 stress-test.js

# Test against production (be careful!)
k6 run -e BASE_URL=https://your-production-url.com stress-test.js
```

## Test Script

### stress-test.js - Enhanced Maximum RPS Test (All Endpoints)

**Purpose**: Find the highest RPS the system can handle by testing all API endpoints with realistic weighted random distribution.

- **Stages**: No warm-up; 50 → 1000 users (~13 minutes total)
- **Strategy**: **Weighted random endpoint selection** - creates realistic load patterns instead of fixed sequential execution
- **Focus**: Peak RPS, maximum concurrent users, realistic traffic distribution
- **Thresholds**: Lenient (allows up to 30% error rate to find breaking point)
- **Optimization**: No sleep delays - maximum throughput for stress testing

**Key Features**:
- ✅ **Weighted Random Distribution**: Endpoints are selected based on realistic usage patterns (home page 30%, settings 20%, etc.)
- ✅ **Modular Architecture**: Each endpoint has its own test function for better organization
- ✅ **Complete Booking Flow**: Includes reservation creation, status checks, and OTP resending
- ✅ **Maximum Throughput**: No artificial delays - pushes system to absolute limits
- ✅ **Per-Endpoint Timing**: Endpoint duration metrics included in the results JSON

**Tested Endpoints** (All 9 endpoints with weighted distribution):
- GET `/` (Home Page) - **30% weight** (most frequent)
- GET `/api/v1/restaurant-settings` - **20% weight** (cached, fast)
- GET `/api/v1/availability` - **15% weight** (database intensive)
- POST `/api/v1/reservations` - **10% weight** (creates reservations, OTP sending bypassed)
- GET `/api/v1/time-slots` - **10% weight**
- GET `/api/v1/closed-dates` - **5% weight** (cached)
- GET `/api/v1/restaurant-settings?date=...` - **5% weight**
- GET `/api/v1/reservation-status` - **3% weight** (checks reservation status)
- POST `/api/v1/resend-otp` - **2% weight** (resends OTP, sending bypassed)

**Complete Booking Flow** (triggered when availability endpoint is selected):
1. Check table availability
2. Create reservation (OTP generated but not sent via WhatsApp)
3. Check reservation status (50% chance if reservation created)
4. Resend OTP (15% chance if status checked)

## Usage Examples

### Basic Stress Test

```bash
# Run stress test to find maximum RPS
k6 run stress-test.js
```

### Control Date Range (Cache Hit Rate)

```bash
# Recommended range for better cache hit rates
k6 run -e DATE_RANGE_DAYS=14 stress-test.js

# Wider range (more DB work, lower cache hit rate)
k6 run -e DATE_RANGE_DAYS=30 stress-test.js
```

### Monitor System Resources

**⚠️ IMPORTANT**: Monitor your system resources while stress tests run!

```bash
# Windows: Open Task Manager or Resource Monitor
# Linux/Mac: Use htop or top in another terminal
htop  # or: top
```

**Database note**: If you changed `docker/mysql/my.cnf` (e.g., buffer pool),
restart MySQL to apply changes:

```bash
docker-compose restart mysql
```

### Custom Virtual Users and Duration

```bash
# Run with custom options
k6 run --vus 500 --duration 300s stress-test.js
```

### Custom Stages

```bash
# Define custom load pattern
k6 run --stage 30s:100 --stage 60s:500 --stage 120s:500 --stage 30s:0 stress-test.js
```

### Output Results to File

```bash
# Output to JSON
k6 run --out json=results/stress-results.json stress-test.js

# Output to InfluxDB (if configured)
k6 run --out influxdb=http://localhost:8086/k6 stress-test.js
```

## Understanding Results

### Key Metrics

- **Peak RPS**: The highest requests per second achieved
- **Total Requests**: Total number of requests made during the test
- **Failed Requests %**: Percentage of requests that failed
- **Response Times**: Average, P95, P99 response times
- **Max Virtual Users**: Maximum concurrent users during test

### What to Look For

1. **Peak RPS**: This is your maximum throughput
2. **Error Rate**: When it exceeds 20-30%, system is overloaded
3. **Response Times**: When P95 exceeds 5-10 seconds, system is struggling
4. **CPU Usage**: Monitor to see when you hit 100% CPU
5. **Memory Usage**: Watch for memory leaks or exhaustion

### System Status Indicators

- ✅ **System handling load well**: Error rate < 10%, response times < 1s
- ⚠️ **System under stress**: Error rate 10-20%, response times 1-3s
- ❌ **System overloaded**: Error rate > 20%, response times > 3s

## Stress Test Result (Summary)
**Environment**: Docker Desktop on Windows, 2 vCPU / 4 GB RAM  
**App runtime**: Laravel Octane (Swoole), `OCTANE_WORKERS=4`, `OCTANE_TASK_WORKERS=2`  
**DB**: MySQL 8, `innodb_buffer_pool_size=1536M` (1.5GB), `max_connections=500`, binary logging disabled  
**Cache**: Redis caching enabled for time slots, settings, and availability endpoints  
**Test profile**: `DATE_RANGE_DAYS=14`, 5-minute warm-up, 20 -> 1000 VUs over 19 minutes  
**Command**: `k6 run -e DATE_RANGE_DAYS=14 stress-test.js`

**Results:**
- **Total Requests**: 75,049
- **Peak RPS**: 65.48 requests/second (all endpoints combined)
- **Failed Requests**: 16.29%
- **Max Virtual Users**: 1000
- **Avg Response Time**: 6,026.72 ms
- **P95 Response Time**: 15,318.53 ms
- **P99 Response Time**: 16,416.32 ms
- **Status**: ⚠️ System under stress, ❌ response times critical

**Note**: Thresholds for `http_req_duration` and `http_req_duration{status:200}` were exceeded in this run.

### Endpoint Distribution (Weighted)
The 65.48 RPS includes all API endpoints with weighted distribution:
- **Home Page** (30%): ~19.6 RPS
- **Restaurant Settings** (20%): ~13.1 RPS  
- **Availability Checks** (15%): ~9.8 RPS
- **Time Slots** (10%): ~6.5 RPS
- **Create Reservation** (10%): ~6.5 RPS ⚠️ **Actual booking capacity**
- **Closed Dates** (5%): ~3.3 RPS
- **Date Settings** (5%): ~3.3 RPS
- **Status Checks** (3%): ~2.0 RPS
- **Resend OTP** (2%): ~1.3 RPS

### Actual Booking Capacity
**⚠️ Important**: The 65 RPS is for ALL endpoints combined, not just bookings.

- **Maximum Theoretical**: ~6-7 bookings/second (at breaking point with 16% errors)
- **Sustainable Production**: ~2-3 bookings/second (with <5% errors, <1s response time)
- **With Optimizations**: ~4-5 bookings/second (peak capacity)

**Daily Capacity** (if evenly distributed):
- Sustainable: ~172,800 - 259,200 bookings/day
- Peak: ~345,600 - 432,000 bookings/day

**Note**: These are stress test results at the breaking point. For production, plan for 30-40% of maximum capacity to ensure good performance and reliability.

### Performance Optimizations Applied
- ✅ Redis caching for time slots and restaurant settings (10-minute TTL)
- ✅ HTTP cache headers (`Cache-Control`) for GET endpoints
- ✅ MySQL buffer pool increased to 1.5GB (optimized for 4GB RAM)
- ✅ Binary logging disabled (reduces write overhead)
- ✅ Improved cache invalidation for reservation operations
- ✅ Optimized availability queries with indexed datetime ranges

### Scaling Suggestions for Higher RPS
- **Move MySQL off the app host** (separate VM/host or faster disk) to remove I/O contention
- **Use read replicas** for read-heavy endpoints (availability, settings)
- **Horizontal scaling**: Add more app servers behind a load balancer
- **Database optimization**: Consider connection pooling, query result caching
- **CDN/Reverse Proxy**: Add Varnish or Nginx cache layer for static/semi-static content

## Tips

1. **Start monitoring before test**: Have resource monitors ready
2. **Watch CPU usage**: The test will push until you hit 100% CPU
3. **Check logs**: Review application logs for errors during stress
4. **Gradual ramp-up**: Tests use gradual ramp-up to find breaking points
5. **Multiple runs**: Run tests multiple times to get consistent results

## Troubleshooting

**k6 not found:**
- Ensure k6 is installed: `k6 version`
- Check your PATH environment variable

**Connection refused:**
- Ensure the application is running
- Check the BASE_URL is correct
- Verify network connectivity

**High error rates:**
- This is expected in stress tests - we're finding the breaking point
- Check application logs for specific errors
- Monitor system resources (CPU, memory, database)

**Out of memory:**
- Reduce VU count
- Reduce test duration
- Close other applications
- Increase system memory

**Test takes too long:**
- Tests are designed to run 12.5 minutes to find maximum capacity
- You can reduce stages or duration if needed
- Use `--duration` flag to limit total time

## Advanced Usage

### Export to Grafana Cloud

```bash
k6 run --out cloud -e K6_CLOUD_TOKEN=your-token stress-test.js
```


### Compare Results

```bash
# Run test and save results
k6 run --out json=results/baseline.json stress-test.js

# Make changes to application, then run again
k6 run --out json=results/after-optimization.json stress-test.js

# Compare the JSON files to see performance improvements
```

## Documentation

For more information, see:
- [k6 Documentation](https://k6.io/docs/)
- [k6 JavaScript API](https://k6.io/docs/javascript-api/)
- [k6 Examples](https://k6.io/docs/examples/)

## Results Location

Test results are automatically saved to:
- **JSON files**: `results/stress-test-[timestamp].json`

Each result file contains:
- Summary metrics (RPS, response times, error rates)
- Full k6 metrics data
- Timestamp and test configuration
