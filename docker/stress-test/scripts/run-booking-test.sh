#!/bin/bash

# Stress test for booking page
# Usage: ./run-booking-test.sh [URL] [DURATION] [CONCURRENT_USERS]

URL=${1:-http://app:80}
DURATION=${2:-30s}
CONCURRENT=${3:-10}

echo "=========================================="
echo "Booking Page Stress Test"
echo "=========================================="
echo "URL: $URL"
echo "Duration: $DURATION"
echo "Concurrent Users: $CONCURRENT"
echo "=========================================="
echo ""

echo "Running wrk test..."
wrk -t4 -c$CONCURRENT -d$DURATION --timeout 10s --latency $URL

echo ""
echo "=========================================="
echo "Test completed!"
echo "=========================================="

