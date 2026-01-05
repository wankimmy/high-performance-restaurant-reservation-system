#!/bin/bash

echo "🛑 Stopping Restaurant Reservation System..."

# Stop and remove containers
docker-compose down

echo ""
echo "✅ All containers stopped and removed."
echo ""
echo "💾 Data volumes are preserved."
echo ""
echo "To remove data volumes as well, run:"
echo "   docker-compose down -v"
echo ""

