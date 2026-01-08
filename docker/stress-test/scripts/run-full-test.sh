#!/bin/bash

# Comprehensive stress test suite
# Usage: ./run-full-test.sh [BASE_URL] [DURATION]

BASE_URL=${1:-http://app:80}
DURATION=${2:-60s}
CONCURRENT=${3:-50}

echo "=========================================="
echo "Comprehensive Stress Test Suite"
echo "=========================================="
echo "Base URL: $BASE_URL"
echo "Duration: $DURATION"
echo "Concurrent Users: $CONCURRENT"
echo "=========================================="
echo ""

# Test 1: Homepage (Booking Page)
echo "Test 1: Homepage Load Test"
echo "----------------------------------------"
wrk -t4 -c$CONCURRENT -d$DURATION --timeout 10s --latency $BASE_URL/
echo ""

# Test 2: API Availability Check
echo "Test 2: API Availability Endpoint"
echo "----------------------------------------"
DATE=$(date +%Y-%m-%d -d '+1 day')
wrk -t4 -c$CONCURRENT -d$DURATION --timeout 10s --latency \
    "$BASE_URL/api/v1/availability?date=$DATE&time=19:00&pax=4"
echo ""

# Test 3: Admin Dashboard (if accessible)
echo "Test 3: Admin Dashboard (may require auth)"
echo "----------------------------------------"
wrk -t2 -c10 -d10s --timeout 10s --latency $BASE_URL/admin/dashboard || echo "Skipped (requires authentication)"
echo ""

echo "=========================================="
echo "All tests completed!"
echo "=========================================="

