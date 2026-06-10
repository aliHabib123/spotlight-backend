# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Lebanon Spotlight is a Laravel 12 REST API backend serving a mobile app. It manages spotlight listings (places of interest), events, tours, news, e-commerce (shops/products/orders), banners, ads, and user authentication. The admin panel is built with Filament v3.

## Commands

```bash
# Development (runs server, queue, log watcher, and Vite concurrently)
composer dev

# Run tests (uses in-memory SQLite)
composer test

# Run a single test file
php artisan test --filter=ExampleTest

# Lint with Pint
./vendor/bin/pint

# Database migrations
php artisan migrate

# Seed roles, permissions, and default users
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=TourRolesAndPermissionsSeeder

# Clear config cache (required after .env changes)
php artisan config:clear

# Storage link (for serving uploaded images)
php artisan storage:link
```

## Architecture

### Authentication (Dual System)
The API uses **two different auth guards** — this is intentional:

- **JWT (`api` guard via `php-open-source-saver/jwt-auth`)**: Used for the mobile app. Protected routes use the custom `JsonApiAuthentication` middleware (not Laravel's standard `auth` middleware). Token passed as `Authorization: Bearer <token>`.
- **Sanctum (`auth:sanctum`)**: Used for some Spotlight management routes (legacy/admin web-facing). Mixed into the same `routes/api.php`.

When adding new protected routes, check which guard the surrounding context uses before choosing middleware.

### Push Notifications (Observer Pattern)
FCM notifications are sent via `FirebaseNotificationService` (singleton), wired through **model observers** registered in `AppServiceProvider`:

- `SpotlightObserver` — on create/update, checks `session('spotlight_send_notification')` before firing. This session key is set by the Filament admin form, not the API.
- `NewsObserver`, `EventObserver`, `TourObserver` — similar pattern.

The session-based toggle (`spotlight_send_notification`) means notifications only fire from admin panel actions, not raw API calls.

Thumbnail generation for Spotlights also happens inside `SpotlightObserver` using `intervention/image` (GD driver). It produces four sizes: base (1200px wide), small (45%), 1200×360 crop, 1080×1080 crop.

### Roles & Permissions
Uses `spatie/laravel-permission`. Roles: `super admin`, `admin`, `finance`, `app user`, plus `tour admin` (seeded separately). Permission strings follow the pattern `"action resource"` (e.g., `"manage categories"`, `"publish spotlights"`).

Route-level guards use `can:manage X` middleware for admin-only mutations.

### Key Domain Models
- **Spotlight**: Central entity. Has category, location, tags (many-to-many), attribute values (EAV-style via `SpotlightAttributeDefinition` → `SpotlightAttributeValue`), media (polymorphic), ratings, and four thumbnail variants.
- **Tour**: Has images, ratings, date ranges, day availabilities, locations, and bookings. Tour bookings integrate with **Whish payment gateway** (`WhishPaymentService`).
- **Event**: Has schedules, locations (separate `EventLocation` model, not the shared `Location`), and categories.
- **Media**: Polymorphic — attached to any model via `mediable_type` / `mediable_id`.

### API Structure
All routes are under `/api/v1/`. Public endpoints require no auth. Write endpoints for Spotlight management use Sanctum; tour/news/event/banner/ad mutations use JWT.

Response format (from `AuthController::apiResponse`):
```json
{ "status": "success|error", "message": "...", "data": {...} }
```

### Filament Admin Panel
Located at `app/Filament/Resources/`. Each resource maps 1:1 to a model. Two custom Filament service providers (`FilamentAdServiceProvider`, `FilamentBannerServiceProvider`) registered in `AppServiceProvider`. The admin panel uses session-based authentication (separate from the API guards).

### External Services
- **Firebase FCM**: Credentials file `lebanon-spotlight-firebase-adminsdk-fbsvc-715d61d491.json` at project root. Controlled by `FCM_ENABLED` env var.
- **Whish Payment**: Lebanese payment gateway. Configured via `config/whish.php` (`WHISH_CHANNEL`, `WHISH_SECRET`, `WHISH_ENV`). Callback routes are public at `/api/whish/callback/success` and `/api/whish/callback/failure`.
- **WeatherKit**: Apple WeatherKit API, used by `WeatherController`.
- **Social Login**: Google and Facebook via `laravel/socialite`.

### Caching
Spotlight index queries are cached for 1 hour using `Cache::remember` with a key derived from all request parameters (`md5(json_encode($request->all()))`). Cache must be cleared manually when spotlight data changes outside of normal observer flows.

### Storage
Uploaded files go to `storage/app/public/`. Run `php artisan storage:link` to serve them from `public/storage/`. Spotlight thumbnails are stored under `storage/app/public/thumbnails/`.
