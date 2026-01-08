# Stress Test Container

This container provides various load testing tools to stress test the restaurant reservation application.

## Tools Included

- **wrk** - High-performance HTTP benchmarking tool
- **Apache Bench (ab)** - Simple HTTP benchmarking tool
- **hey** - Modern HTTP load testing tool
- **curl** - Command-line HTTP client
- **jq** - JSON processor

## Quick Start

### Build the Container

```bash
docker-compose build stress-test
```

### Run Pre-configured Tests

#### 1. Booking Page Stress Test
```bash
docker-compose run --rm stress-test ./run-booking-test.sh http://app:80 30s 50
```

#### 2. API Endpoint Test
```bash
docker-compose run --rm stress-test ./run-api-test.sh http://app:80 30 20
```

#### 3. Comprehensive Test Suite
```bash
docker-compose run --rm stress-test ./run-full-test.sh http://app:80 60s 100
```

#### 4. Apache Bench Test
```bash
docker-compose run --rm stress-test ./run-apache-bench.sh http://app:80 10000 50
```

## Interactive Usage

### Access the Container Shell

```bash
docker-compose run --rm stress-test bash
```

### Manual wrk Commands

```bash
# Basic test: 4 threads, 50 connections, 30 seconds
wrk -t4 -c50 -d30s --latency http://app:80/

# Test with custom headers
wrk -t4 -c50 -d30s -H "User-Agent: StressTest" --latency http://app:80/

# Test API endpoint
wrk -t4 -c50 -d30s --latency "http://app:80/api/v1/availability?date=2024-01-15&time=19:00&pax=4"
```

### Manual Apache Bench Commands

```bash
# 1000 requests, 10 concurrent
ab -n 1000 -c 10 http://app:80/

# Keep-alive connections
ab -n 1000 -c 10 -k http://app:80/

# Save results to file
ab -n 10000 -c 50 -g results.tsv http://app:80/
```

### Manual hey Commands

```bash
# 1000 requests, 10 concurrent
hey -n 1000 -c 10 http://app:80/

# 20 requests per second for 30 seconds
hey -z 30s -q 20 http://app:80/

# POST request
hey -n 1000 -c 10 -m POST -H "Content-Type: application/json" \
    -d '{"key":"value"}' http://app:80/api/v1/reservations
```

## Test Scenarios

### Light Load Test
```bash
docker-compose run --rm stress-test wrk -t2 -c10 -d10s --latency http://app:80/
```

### Medium Load Test
```bash
docker-compose run --rm stress-test wrk -t4 -c50 -d30s --latency http://app:80/
```

### Heavy Load Test
```bash
docker-compose run --rm stress-test wrk -t8 -c200 -d60s --latency http://app:80/
```

### Extreme Load Test
```bash
docker-compose run --rm stress-test wrk -t12 -c500 -d120s --latency http://app:80/
```

## Testing Different Server Types

### Test with Nginx (Default)
```bash
# Ensure SERVER_TYPE=nginx in docker-compose.yml or .env
docker-compose up -d app
docker-compose run --rm stress-test ./run-full-test.sh http://app:80 60s 100
```

### Test with Swoole/Octane
```bash
# Set SERVER_TYPE=swoole in docker-compose.yml or .env
SERVER_TYPE=swoole docker-compose up -d app
docker-compose run --rm stress-test ./run-full-test.sh http://app:80 60s 100
```

## Understanding Results

### wrk Output

- **Requests/sec**: Throughput (higher is better)
- **Latency**: Response time distribution
- **Transfer/sec**: Data transfer rate

### Apache Bench Output

- **Requests per second**: Throughput
- **Time per request**: Average response time
- **Failed requests**: Number of failed requests

### Key Metrics to Monitor

1. **Response Time**: Should be < 200ms for most requests
2. **Throughput**: Requests per second
3. **Error Rate**: Should be 0% or very low
4. **CPU/Memory**: Monitor resource usage during tests

## Tips

1. **Start Small**: Begin with light load tests and gradually increase
2. **Monitor Resources**: Watch CPU, memory, and database connections
3. **Test Different Endpoints**: Test both static pages and API endpoints
4. **Compare Server Types**: Test both Nginx and Swoole configurations
5. **Use Laravel Pulse**: Monitor application performance during tests at http://localhost:8000/pulse

## Troubleshooting

### Container Not Found
```bash
# Rebuild the container
docker-compose build stress-test
```

### Connection Refused
```bash
# Ensure app container is running
docker-compose ps app

# Check if app is accessible
docker-compose exec stress-test curl http://app:80/
```

### Out of Memory
```bash
# Reduce concurrent connections
docker-compose run --rm stress-test wrk -t2 -c20 -d30s http://app:80/
```

