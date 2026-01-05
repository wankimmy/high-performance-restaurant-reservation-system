# Makefile for Restaurant Reservation System
# Simplifies common Docker and Laravel commands

.PHONY: help build up down restart logs shell test clean install

# Default target - show help
help:
	@echo "Restaurant Reservation System - Command Reference"
	@echo ""
	@echo "Setup Commands:"
	@echo "  make build      - Build Docker images"
	@echo "  make up         - Start all containers"
	@echo "  make install    - Full setup (build + up)"
	@echo ""
	@echo "Container Management:"
	@echo "  make down       - Stop and remove containers"
	@echo "  make restart    - Restart all containers"
	@echo "  make logs       - View container logs"
	@echo "  make ps         - Show container status"
	@echo ""
	@echo "Application Commands:"
	@echo "  make shell      - Access application shell"
	@echo "  make tinker     - Laravel Tinker REPL"
	@echo "  make test       - Run tests"
	@echo "  make migrate    - Run database migrations"
	@echo "  make seed       - Seed database"
	@echo ""
	@echo "Quick Access:"
	@echo "  make admin      - Open admin panel (http://localhost:8000/admin/dashboard)"
	@echo "  make booking    - Open booking page (http://localhost:8000)"
	@echo "  make db         - Open phpMyAdmin (http://localhost:8080)"
	@echo ""
	@echo "Database Management:"
	@echo "  make mysql      - MySQL command line"
	@echo ""
	@echo "Cache Commands:"
	@echo "  make cache      - Cache config, routes, views"
	@echo "  make clear      - Clear all caches"
	@echo ""
	@echo "Maintenance:"
	@echo "  make clean      - Clean up Docker resources"
	@echo "  make fresh      - Fresh install (removes data!)"

# Build Docker images
build:
	@echo "Building Docker images..."
	docker-compose build

# Start containers
up:
	@echo "Starting containers..."
	docker-compose up -d
	@echo "Waiting for services to be ready..."
	@sleep 5
	@echo "✓ Application ready at http://localhost:8000"

# Full installation
install: build up
	@echo "✓ Installation complete!"
	@echo "Visit http://localhost:8000"
	@echo "Admin: admin@restaurant.com / password"

# Stop containers
down:
	@echo "Stopping containers..."
	docker-compose down

# Restart containers
restart:
	@echo "Restarting containers..."
	docker-compose restart

# View logs
logs:
	docker-compose logs -f

# Show container status
ps:
	docker-compose ps

# Access application shell
shell:
	docker-compose exec app bash

# Laravel Tinker
tinker:
	docker-compose exec app php artisan tinker

# Run tests
test:
	@echo "Running tests..."
	docker-compose exec app php artisan test

# Run migrations
migrate:
	@echo "Running migrations..."
	docker-compose exec app php artisan migrate

# Seed database
seed:
	@echo "Seeding database..."
	docker-compose exec app php artisan db:seed

# Open admin panel in browser
admin:
	@echo "Opening Admin Panel..."
	@powershell -Command "Start-Process 'http://localhost:8000/admin/dashboard'"

# Open booking page in browser
booking:
	@echo "Opening Booking Page..."
	@powershell -Command "Start-Process 'http://localhost:8000'"

# Open phpMyAdmin in browser
db:
	@echo "Opening phpMyAdmin..."
	@powershell -Command "Start-Process 'http://localhost:8080'"

# MySQL command line
mysql:
	@echo "Accessing MySQL..."
	docker-compose exec mysql mysql -u restaurant_user -p restaurant_reservation

# Cache everything
cache:
	@echo "Caching configuration..."
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache
	@echo "✓ Cache updated"

# Clear all caches
clear:
	@echo "Clearing caches..."
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear
	@echo "✓ Caches cleared"

# Clean up Docker resources
clean:
	@echo "Cleaning up Docker resources..."
	docker system prune -f
	@echo "✓ Cleanup complete"

# Fresh install (removes all data!)
fresh:
	@echo "WARNING: This will remove all data!"
	@read -p "Are you sure? (y/N): " confirm && [ $$confirm = y ] || exit 1
	@echo "Removing containers and volumes..."
	docker-compose down -v
	@echo "Building and starting..."
	docker-compose build
	docker-compose up -d
	@echo "✓ Fresh installation complete!"

