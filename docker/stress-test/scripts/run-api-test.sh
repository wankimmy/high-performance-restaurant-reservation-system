#!/bin/bash

# Stress test for API endpoints
# Usage: ./run-api-test.sh [BASE_URL] [DURATION] [REQUESTS_PER_SECOND]

BASE_URL=${1:-http://app:80}
DURATION=${2:-30}
RPS=${3:-10}

echo "=========================================="
echo "API Endpoint Stress Test"
echo "=========================================="
echo "Base URL: $BASE_URL"
echo "Duration: ${DURATION}s"
echo "Requests per second: $RPS"
echo "=========================================="
echo ""

# Test availability endpoint
echo "Testing /api/v1/availability endpoint..."
hey -n $((RPS * DURATION)) -c $RPS -m GET \
    "$BASE_URL/api/v1/availability?date=$(date +%Y-%m-%d -d '+1 day')&time=19:00&pax=4"

echo ""
echo "=========================================="
echo "Test completed!"
echo "=========================================="

