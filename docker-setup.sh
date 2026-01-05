#!/bin/bash

echo "🐳 Setting up Restaurant Reservation System with Docker..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create .env file from .env.docker if not exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.docker..."
    cp .env.docker .env
else
    echo "⚠️  .env file already exists. Skipping..."
fi

# Create necessary directories
echo "📁 Creating necessary directories..."
mkdir -p docker/nginx/conf.d
mkdir -p docker/php
mkdir -p docker/mysql
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set permissions
echo "🔒 Setting permissions..."
chmod -R 775 storage bootstrap/cache

# Build and start containers
echo "🏗️  Building Docker containers..."
docker-compose build

echo "🚀 Starting Docker containers..."
docker-compose up -d

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
until docker-compose exec -T mysql mysqladmin ping -h localhost --silent; do
    printf "."
    sleep 2
done
echo ""

# Generate application key
echo "🔑 Generating application key..."
docker-compose exec -T app php artisan key:generate

# Run migrations
echo "📊 Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Seed database
echo "🌱 Seeding database..."
docker-compose exec -T app php artisan db:seed --force

# Cache configuration
echo "⚡ Optimizing application..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

echo ""
echo "✅ Setup complete!"
echo ""
echo "📌 Application URLs:"
echo "   - Booking Page: http://localhost:8000"
echo "   - Admin Login:  http://localhost:8000/login"
echo "   - Admin Panel:  http://localhost:8000/admin/reservations"
echo ""
echo "🔐 Default Admin Credentials:"
echo "   Email:    admin@restaurant.com"
echo "   Password: password"
echo ""
echo "⚠️  Please change the default password after first login!"
echo ""
echo "📊 Service Status:"
docker-compose ps
echo ""
echo "📝 Useful commands:"
echo "   View logs:        docker-compose logs -f"
echo "   Stop containers:  docker-compose stop"
echo "   Start containers: docker-compose start"
echo "   Restart:          docker-compose restart"
echo "   Remove all:       docker-compose down -v"
echo ""

