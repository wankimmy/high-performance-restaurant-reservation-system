# Contributing Guide

> Thank you for contributing to the Restaurant Reservation System!

---

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Pull Request Process](#pull-request-process)
- [Project Structure](#project-structure)

---

## 🤝 Code of Conduct

- Be respectful and inclusive
- Welcome newcomers
- Focus on constructive feedback
- Prioritize user experience

---

## 🚀 Getting Started

### 1. Fork & Clone

```bash
# Fork on GitHub, then clone your fork
git clone https://github.com/YOUR_USERNAME/restaurant-system.git
cd restaurant-system
```

### 2. Set Up Development Environment

```bash
# Build and start containers
docker-compose build
docker-compose up -d

# Verify everything is running
docker-compose ps
```

### 3. Create a Branch

```bash
git checkout -b feature/your-feature-name
# or
git checkout -b fix/your-bug-fix
```

---

## 💻 Development Workflow

### Running the Application

```bash
# Start services
docker-compose up -d

# View logs
docker-compose logs -f

# Access container
docker-compose exec app bash
```

### Making Changes

1. **Laravel Code** - Files auto-reload (volume mounted)
2. **Blade Views** - Clear view cache: `docker-compose exec app php artisan view:clear`
3. **Routes** - Clear route cache: `docker-compose exec app php artisan route:clear`
4. **Config** - Clear config cache: `docker-compose exec app php artisan config:clear`
5. **Docker Config** - Rebuild: `docker-compose build && docker-compose up -d`

### Database Changes

```bash
# Create migration
docker-compose exec app php artisan make:migration create_something_table

# Run migrations
docker-compose exec app php artisan migrate

# Rollback
docker-compose exec app php artisan migrate:rollback

# Seed data
docker-compose exec app php artisan db:seed
```

---

## 📝 Coding Standards

### PHP / Laravel

Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards.

```bash
# Format code with Laravel Pint
docker-compose exec app ./vendor/bin/pint

# Check specific file
docker-compose exec app ./vendor/bin/pint app/Http/Controllers/YourController.php
```

### Key Principles

1. **Single Responsibility** - One class, one purpose
2. **DRY** - Don't Repeat Yourself
3. **KISS** - Keep It Simple, Stupid
4. **YAGNI** - You Aren't Gonna Need It
5. **Comments** - Explain WHY, not WHAT

### Naming Conventions

```php
// Controllers: Singular noun + Controller
class ReservationController extends Controller

// Models: Singular noun
class Reservation extends Model

// Database tables: Plural snake_case
Schema::create('reservations', function (Blueprint $table) {

// Variables: camelCase
$customerName = 'John Doe';

// Constants: UPPER_SNAKE_CASE
const MAX_RESERVATIONS_PER_HOUR = 3;

// Methods: camelCase verbs
public function createReservation()

// Routes: kebab-case
Route::get('/admin/reservations', ...);
```

### File Organization

```
app/
├── Console/Commands/      # Artisan commands
├── Http/
│   ├── Controllers/       # Request handlers
│   │   ├── Api/          # API endpoints
│   │   ├── Admin/        # Admin panel
│   │   └── Auth/         # Authentication
│   ├── Middleware/        # HTTP middleware
│   └── Requests/          # Form requests (validation)
├── Jobs/                  # Queue jobs
├── Models/                # Eloquent models
├── Services/              # Business logic
└── Providers/             # Service providers
```

---

## 🧪 Testing

### Running Tests

```bash
# Run all tests
docker-compose exec app php artisan test

# Run specific test file
docker-compose exec app php artisan test --filter ReservationTest

# Run with coverage
docker-compose exec app php artisan test --coverage
```

### Writing Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_reservation(): void
    {
        $response = $this->post('/api/reservations', [
            'table_id' => 1,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'pax' => 4,
            'reservation_date' => '2024-01-15',
            'reservation_time' => '19:00',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reservations', [
            'customer_email' => 'john@example.com',
        ]);
    }
}
```

### Test Coverage

Aim for:
- **Controllers**: 80%+ coverage
- **Models**: 90%+ coverage
- **Services**: 90%+ coverage
- **Critical paths**: 100% coverage

---

## 📤 Pull Request Process

### Before Submitting

1. **Test your changes**
   ```bash
   docker-compose exec app php artisan test
   ```

2. **Format code**
   ```bash
   docker-compose exec app ./vendor/bin/pint
   ```

3. **Update documentation** if needed

4. **Commit messages**
   ```
   feat: Add table booking feature
   fix: Resolve queue worker timeout
   docs: Update deployment guide
   refactor: Simplify reservation logic
   test: Add reservation API tests
   ```

### Creating Pull Request

1. Push to your fork
   ```bash
   git push origin feature/your-feature-name
   ```

2. Open PR on GitHub

3. Fill out PR template:
   - **Description**: What does this PR do?
   - **Motivation**: Why is this change needed?
   - **Testing**: How was it tested?
   - **Screenshots**: If UI changes
   - **Checklist**: Mark all items

### PR Review Process

1. Automated checks run (tests, linting)
2. Maintainer reviews code
3. Address feedback
4. Approval & merge

---

## 📁 Project Structure

### Key Directories

```
├── app/                    # Application logic
│   ├── Console/           # CLI commands
│   ├── Http/              # Web layer
│   ├── Jobs/              # Background jobs
│   └── Models/            # Data models
│
├── config/                 # Configuration files
│   ├── app.php            # App settings
│   ├── database.php       # Database config
│   └── queue.php          # Queue config
│
├── database/
│   ├── migrations/        # Database schema
│   └── seeders/           # Sample data
│
├── docker/                 # Docker configuration
│   ├── nginx/             # Web server config
│   ├── php/               # PHP settings
│   ├── supervisor/        # Process manager
│   └── scripts/           # Startup scripts
│
├── public/                 # Public assets
│   └── css/               # Stylesheets
│
├── resources/
│   └── views/             # Blade templates
│
├── routes/                 # Route definitions
│   ├── api.php            # API routes
│   ├── web.php            # Web routes
│   └── console.php        # CLI routes
│
└── storage/                # Generated files
    ├── app/               # Uploaded files
    ├── framework/         # Framework cache
    └── logs/              # Log files
```

### Important Files

| File | Purpose |
|------|---------|
| `docker-compose.yml` | Container orchestration |
| `Dockerfile` | Application container |
| `composer.json` | PHP dependencies |
| `bootstrap/app.php` | App bootstrap |
| `config/app.php` | Main configuration |

---

## 🔧 Common Tasks

### Add New Feature

1. Create migration (if needed)
   ```bash
   docker-compose exec app php artisan make:migration add_feature
   ```

2. Create model
   ```bash
   docker-compose exec app php artisan make:model Feature -m
   ```

3. Create controller
   ```bash
   docker-compose exec app php artisan make:controller FeatureController
   ```

4. Add routes in `routes/web.php` or `routes/api.php`

5. Create tests
   ```bash
   docker-compose exec app php artisan make:test FeatureTest
   ```

### Add API Endpoint

1. Create controller
   ```bash
   docker-compose exec app php artisan make:controller Api/FeatureController --api
   ```

2. Add route in `routes/api.php`
   ```php
   Route::apiResource('features', Api\FeatureController::class);
   ```

3. Add validation
   ```bash
   docker-compose exec app php artisan make:request StoreFeatureRequest
   ```

### Add Background Job

1. Create job
   ```bash
   docker-compose exec app php artisan make:job ProcessFeature
   ```

2. Dispatch in controller
   ```php
   ProcessFeature::dispatch($data);
   ```

3. Test locally
   ```bash
   docker-compose exec app php artisan queue:work --once
   ```

### Add Middleware

1. Create middleware
   ```bash
   docker-compose exec app php artisan make:middleware CheckFeature
   ```

2. Register in `bootstrap/app.php`
   ```php
   ->withMiddleware(function (Middleware $middleware) {
       $middleware->alias([
           'check.feature' => \App\Http\Middleware\CheckFeature::class,
       ]);
   })
   ```

3. Use in routes
   ```php
   Route::get('/feature', ...)->middleware('check.feature');
   ```

---

## 🐛 Debugging

### View Logs

```bash
# All logs
docker-compose logs -f

# App logs
docker-compose logs -f app

# Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log
```

### Debug in Tinker

```bash
docker-compose exec app php artisan tinker

> $reservation = \App\Models\Reservation::find(1);
> dd($reservation->toArray());
```

### Enable Query Logging

Add to `AppServiceProvider`:

```php
use Illuminate\Support\Facades\DB;

public function boot(): void
{
    if (config('app.debug')) {
        DB::listen(function ($query) {
            logger($query->sql, $query->bindings);
        });
    }
}
```

---

## 📚 Resources

- [Laravel Documentation](https://laravel.com/docs)
- [PHP The Right Way](https://phptherightway.com)
- [PSR Standards](https://www.php-fig.org/psr/)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [Git Commit Messages](https://chris.beams.io/posts/git-commit/)

---

## ❓ Questions?

- Check existing [issues](https://github.com/your-repo/issues)
- Review [documentation](README.md)
- Ask in discussions

---

**Happy coding! 🚀**

