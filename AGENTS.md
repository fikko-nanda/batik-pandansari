# Batik Pandansari - Laravel 13 E-Commerce

## Quick Commands

```bash
# Full setup (install deps, env, key, migrate, build assets)
composer setup

# Development server (Laravel + Vite via concurrently)
composer dev

# Run tests (clears config first)
composer test

# Frontend only
npm run dev      # Vite dev server
npm run build    # Production build

# Code formatting
./vendor/bin/pint
```

## Stack

- **PHP 8.3+**, Laravel 13
- **Vite** + TailwindCSS 3 (resources/css/app.css, resources/js/app.js)
- **SQLite** (file: `database/database.sqlite`, testing: `:memory:`)
- **Pest/PHPUnit** for testing
- **Laravel Pint** for code style

## Testing

```bash
# All tests
composer test

# Single test file
./vendor/bin/pest tests/Feature/ExampleTest.php

# Unit tests only
./vendor/bin/pest --testsuite=Unit

# Feature tests only
./vendor/bin/pest --testsuite=Feature
```

Tests use in-memory SQLite, `RefreshDatabase` trait available.

## Project Structure

```
app/              # App code (Http, Models, Providers)
resources/
  views/          # Blade templates (landing, shop/*, customer/*)
  css/app.css     # Tailwind entry
  js/app.js       # JS entry
routes/web.php    # All web routes (e-commerce modules)
database/
  migrations/     # Standard Laravel + jobs/cache tables
  factories/      # Model factories
  seeders/        # DatabaseSeeder
tests/
  Feature/        # HTTP/integration tests
  Unit/           # Unit tests
```

## Key Routes (web.php)

- `/` - Landing page
- `/katalog` - Product catalog
- `/detail/{id}` - Product detail
- `/cart`, `/checkout` - Shopping flow
- `/profile`, `/orders`, `/address`, `/wishlist`, `/security` - Customer area

## Environment

- Copy `.env.example` → `.env` (done by `composer setup`)
- `APP_KEY` generated on setup
- SQLite file auto-created on migrate

## Conventions

- Controllers in `app/Http/Controllers/`
- Models in `app/Models/`
- Views use `resources/views/` with module subdirs (`shop/`, `customer/`)
- Tailwind via Vite - run `npm run build` after CSS/JS changes
- Run `./vendor/bin/pint` before committing