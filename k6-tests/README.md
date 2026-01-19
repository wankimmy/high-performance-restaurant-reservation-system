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

### stress-test.js - Maximum RPS Test (All Endpoints)

**Purpose**: Find the highest RPS the system can handle by testing all API endpoints including the complete booking flow.

- **Stages**: 50 → 1000 users over 750 seconds (12.5 minutes)
- **Strategy**: Tests all endpoints to maximize throughput and find breaking points
- **Focus**: Peak RPS, maximum concurrent users, complete booking flow performance
- **Thresholds**: Lenient (allows up to 30% error rate to find breaking point)

**Tested Endpoints** (All 9 endpoints including home page):
- GET `/` (Home Page)
- GET `/api/v1/restaurant-settings`
- GET `/api/v1/closed-dates`
- GET `/api/v1/time-slots`
- GET `/api/v1/availability`
- GET `/api/v1/restaurant-settings?date=...`
- POST `/api/v1/reservations` (creates reservations, OTP sending bypassed for k6 tests)
- GET `/api/v1/reservation-status` (checks reservation status)
- POST `/api/v1/resend-otp` (resends OTP, sending bypassed for k6 tests)

**Complete Booking Flow**:
1. Get restaurant settings
2. Get closed dates
3. Get time slots for a date
4. Check table availability
5. Create reservation (OTP generated but not sent via WhatsApp)
6. Check reservation status
7. Resend OTP (20% chance, OTP generated but not sent via WhatsApp)

## Usage Examples

### Basic Stress Test

```bash
# Run stress test to find maximum RPS
k6 run stress-test.js
```

### Monitor System Resources

**⚠️ IMPORTANT**: Monitor your system resources while stress tests run!

```bash
# Windows: Open Task Manager or Resource Monitor
# Linux/Mac: Use htop or top in another terminal
htop  # or: top
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
