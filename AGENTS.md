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

## Tim & Dokumentasi

- **Frontend:** team frontend (laptop ini) — Blade views, Tailwind, Alpine.js di `resources/`
- **Backend:** team lain — controller, model, migrasi, auth, integrasi Midtrans/RajaOngkir
- **Dokumentasi frontend & kontrak data backend:** lihat [`docs/FRONTEND.md`](docs/FRONTEND.md)

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
app/              # App code (Http, Models, Providers) — milik team BACKEND
resources/
  views/
    layouts/      # app (publik), admin, teknisi
    components/   # SEMUA komponen anonim <x-...> (auto-discovery Laravel)
      admin/      #   <x-admin.sidebar>, <x-admin.navbar>, <x-admin.modal-add-product>
      teknisi/    #   <x-teknisi.sidebar>
    landing.blade.php, shop/, customer/, auth/, admin/, teknisi/
  css/app.css     # Tailwind entry
  js/app.js       # JS entry (masih kosong — Alpine via CDN)
routes/web.php    # All web routes (e-commerce modules) — milik team BACKEND
database/
  migrations/     # users, cache, jobs, categories, products (belum ada tabel order/cart)
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

- Controllers in `app/Http/Controllers/` — milik team BACKEND
- Models in `app/Models/` — milik team BACKEND
- Views use `resources/views/` with module subdirs (`shop/`, `customer/`) — milik team FRONTEND
- Komponen Blade reusable ditaruh di `resources/views/components/` dan dipanggil sebagai anonim: `<x-navbar />`, `<x-product-card />`, `<x-admin.sidebar />` — JANGAN pakai `@include` untuk komponen
- Halaman mengisi konten via `@section('content')` dan `@extends('layouts.app' | 'layouts.admin' | 'layouts.teknisi')`
- `product-card` menerima props: `badge`, `subtitle`, `title`, `description`, `price`, `material`, `image`
- Tailwind via Vite - run `npm run build` after CSS/JS changes
- Validasi sintaks Blade: `php artisan view:cache` lalu `php artisan view:clear`
- Run `./vendor/bin/pint` before committing