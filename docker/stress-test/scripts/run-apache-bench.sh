#!/bin/bash

# Apache Bench stress test
# Usage: ./run-apache-bench.sh [URL] [TOTAL_REQUESTS] [CONCURRENT]

URL=${1:-http://app:80}
TOTAL=${2:-1000}
CONCURRENT=${3:-10}

echo "=========================================="
echo "Apache Bench Stress Test"
echo "=========================================="
echo "URL: $URL"
echo "Total Requests: $TOTAL"
echo "Concurrent Requests: $CONCURRENT"
echo "=========================================="
echo ""

ab -n $TOTAL -c $CONCURRENT -k -g /tmp/ab_results.tsv $URL

echo ""
echo "Results saved to /tmp/ab_results.tsv"
echo "=========================================="
echo "Test completed!"
echo "=========================================="

