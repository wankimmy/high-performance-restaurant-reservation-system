# Stress Testing Guide

This guide explains how to use the stress test container to load test the restaurant reservation system.

## Prerequisites

- Docker and Docker Compose installed
- Application running (`docker-compose up -d`)

## Quick Start

### 1. Build the Stress Test Container

```bash
docker-compose build stress-test
```

### 2. Run a Quick Test

```bash
# Test the homepage with 50 concurrent users for 30 seconds
docker-compose run --rm stress-test wrk -t4 -c50 -d30s --latency http://app:80/
```

## Available Test Scripts

All test scripts are located in `docker/stress-test/scripts/`:

### `run-booking-test.sh`
Tests the booking page (homepage).

```bash
docker-compose run --rm stress-test ./run-booking-test.sh http://app:80 30s 50
```

Parameters:
- URL (default: `http://app:80`)
- Duration (default: `30s`)
- Concurrent users (default: `10`)

### `run-api-test.sh`
Tests the API availability endpoint.

```bash
docker-compose run --rm stress-test ./run-api-test.sh http://app:80 30 20
```

Parameters:
- Base URL (default: `http://app:80`)
- Duration in seconds (default: `30`)
- Requests per second (default: `10`)

### `run-full-test.sh`
Comprehensive test suite covering multiple endpoints.

```bash
docker-compose run --rm stress-test ./run-full-test.sh http://app:80 60s 100
```

Parameters:
- Base URL (default: `http://app:80`)
- Duration (default: `60s`)
- Concurrent users (default: `50`)

### `run-apache-bench.sh`
Apache Bench stress test.

```bash
docker-compose run --rm stress-test ./run-apache-bench.sh http://app:80 10000 50
```

Parameters:
- URL (default: `http://app:80`)
- Total requests (default: `1000`)
- Concurrent requests (default: `10`)

## Test Scenarios

### Light Load (Development Testing)
```bash
docker-compose run --rm stress-test wrk -t2 -c10 -d10s --latency http://app:80/
```

### Medium Load (Normal Traffic)
```bash
docker-compose run --rm stress-test wrk -t4 -c50 -d30s --latency http://app:80/
```

### Heavy Load (Peak Traffic)
```bash
docker-compose run --rm stress-test wrk -t8 -c200 -d60s --latency http://app:80/
```

### Extreme Load (Stress Test)
```bash
docker-compose run --rm stress-test wrk -t12 -c500 -d120s --latency http://app:80/
```

## Comparing Server Types

### Test with Nginx (Default)

```bash
# Ensure SERVER_TYPE=nginx
docker-compose up -d app

# Run test
docker-compose run --rm stress-test ./run-full-test.sh http://app:80 60s 100
```

### Test with Swoole/Octane

```bash
# Set SERVER_TYPE=swoole
SERVER_TYPE=swoole docker-compose up -d app

# Run same test
docker-compose run --rm stress-test ./run-full-test.sh http://app:80 60s 100
```

Compare the results to see performance differences!

## Monitoring During Tests


### Container Resources
```bash
# Monitor app container resources
docker stats restaurant_app

# Monitor all containers
docker stats
```

### Application Logs
```bash
# View application logs
docker-compose logs -f app

# View specific service logs
docker-compose exec app supervisorctl tail -f laravel-queue
```

## Understanding Results

### wrk Output Example

```
Running 30s test @ http://app:80/
  4 threads and 50 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency    45.23ms   12.34ms  234.56ms   87.65%
    Req/Sec   275.45    45.67   450.00     78.90%
  33054 requests in 30.00s, 12.34MB read
Requests/sec: 1101.80
Transfer/sec:    421.23KB
```

**Key Metrics:**
- **Requests/sec**: Throughput (higher is better)
- **Latency**: Response time (lower is better)
- **Transfer/sec**: Data transfer rate

### Apache Bench Output Example

```
Requests per second:    1101.80 [#/sec] (mean)
Time per request:       45.23 [ms] (mean)
Time per request:       0.91 [ms] (mean, across all concurrent requests)
Transfer rate:          421.23 [Kbytes/sec] received
```

## Best Practices

1. **Start Small**: Begin with light load and gradually increase
2. **Monitor Resources**: Watch CPU, memory, and database during tests
3. **Test Realistic Scenarios**: Test actual user workflows
4. **Compare Configurations**: Test both Nginx and Swoole setups
5. **Document Results**: Keep track of performance metrics
6. **Test Different Endpoints**: Don't just test the homepage
7. **Monitor**: Check application logs and metrics

## Troubleshooting

### Container Build Fails
```bash
# Rebuild without cache
docker-compose build --no-cache stress-test
```

### Connection Refused
```bash
# Check if app is running
docker-compose ps app

# Test connectivity
docker-compose run --rm stress-test curl -v http://app:80/
```

### Out of Memory Errors
```bash
# Reduce concurrent connections
docker-compose run --rm stress-test wrk -t2 -c20 -d30s http://app:80/
```

### Slow Response Times
- Check database connection pool
- Monitor Redis cache hit rates
- Review application logs for slow queries
- Check queue worker status

## Advanced Usage

### Custom Test Script

Create your own test script in `docker/stress-test/scripts/`:

```bash
#!/bin/bash
# custom-test.sh

URL=${1:-http://app:80}
CONCURRENT=${2:-50}

echo "Testing booking flow..."
wrk -t4 -c$CONCURRENT -d30s --script=booking.lua $URL/
```

### Using Lua Scripts with wrk

wrk supports Lua scripts for complex test scenarios. Create scripts in the container:

```bash
docker-compose run --rm stress-test bash
# Inside container, create test.lua
```

Example `test.lua`:
```lua
request = function()
    path = "/api/v1/availability?date=2024-01-15&time=19:00&pax=4"
    return wrk.format("GET", path)
end
```

Then run:
```bash
wrk -t4 -c50 -d30s --script=test.lua http://app:80/
```

## Resources

- [wrk Documentation](https://github.com/wg/wrk)
- [Apache Bench Documentation](https://httpd.apache.org/docs/2.4/programs/ab.html)
- [hey Documentation](https://github.com/rakyll/hey)

