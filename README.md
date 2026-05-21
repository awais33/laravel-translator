# Laravel Translation Management Service

An API-driven translation management system that supports multiple locales, contextual tagging, full-text search, and a high-performance JSON export endpoint.

## Features

- Multi-locale translation storage (en, fr, es, and any future languages)
- Tag-based context grouping (mobile, desktop, web, etc.)
- Full CRUD API with search by key, content, or tags
- JSON export endpoint optimized for frontend consumption (Vue.js, React, etc.)
- Token-based authentication via Laravel Sanctum
- Response times under 200ms for CRUD, under 500ms for bulk export
- Database seeder for 100k+ records to validate scalability

## Tech Stack

- **PHP 8.3** / **Laravel 13**
- **PostgreSQL 16** — primary datastore with optimized indexes
- **Redis** — caching layer for the export endpoint
- **Laravel Sanctum** — API token authentication
- **Docker** — containerized development and deployment

## Design Choices

### Repository + Service Pattern

The service layer (`TranslationService`) handles business logic — duplicate key detection, tag resolution, cache invalidation — while the repository (`TranslationRepository`) owns all database access. Controllers stay thin and only deal with HTTP concerns. This separation makes each class independently testable and keeps the codebase easy to extend.

### Database Schema

```
locales          translations               tags          translation_tag
--------         -----------------          ----          ---------------
id               id                         id            translation_id
code (unique)    locale_id (FK)             name (unique) tag_id
name             key                        timestamps
is_active        value
timestamps       group
                 unique(locale_id, key)
                 index(key)
                 index(group)
```

The `(locale_id, key)` unique constraint enforces data integrity at the database level, and the individual index on `key` makes key-based lookups and searches fast regardless of locale.

### Export Caching

The export endpoint (`GET /api/export/{locale}`) is the highest-read endpoint. Its response is cached in Redis with a 24-hour TTL. Any write operation (create, update, delete) on a translation triggers a targeted cache flush for that locale — so the data is always fresh without polling.

### Why No External CRUD Packages

The implementation uses only Laravel's built-in Eloquent, query builder, validation, and resource system. No Spatie Translatable, astrotomic/translatable, or similar packages. This gives full control over the schema and query paths.

## Setup

### With Docker (recommended)

```bash
cp .env.example .env
# Edit .env — set DB_HOST=postgres, DB_USERNAME=translator, DB_PASSWORD=secret, REDIS_HOST=redis
docker compose up -d
docker compose exec app php artisan migrate --seed
```

The API will be available at `http://localhost:8000/api`.

### Local Setup

**Requirements:** PHP 8.3, Composer, PostgreSQL 16, Redis

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configure your .env DB_ and REDIS_ values, then:
php artisan migrate
php artisan db:seed

php artisan serve
```

### Seed 100k+ Records

```bash
php artisan translations:seed
```

This populates 100,000 translations across locales with random tag assignments. Runs in batches of 500 to avoid memory pressure.

## API Reference

See [openapi.yaml](openapi.yaml) for the full OpenAPI 3.0 spec.

### Authentication

```
POST /api/auth/register   — create account, returns token
POST /api/auth/login      — returns token
POST /api/auth/logout     — revokes current token  [auth required]
GET  /api/auth/me         — current user           [auth required]
```

### Translations  `[auth required]`

```
GET    /api/translations              — list (paginated)
POST   /api/translations              — create
GET    /api/translations/{id}         — show
PUT    /api/translations/{id}         — update
DELETE /api/translations/{id}         — delete
```

**Query parameters for listing/searching:**

| Param      | Description                             |
|------------|-----------------------------------------|
| `locale`   | Filter by locale code (e.g. `en`)       |
| `tag`      | Filter by tag name (e.g. `mobile`)      |
| `search`   | Full-text search on key or value        |
| `key`      | Exact key match                         |
| `group`    | Filter by group                         |
| `per_page` | Results per page (max 100, default 20)  |

### Export (public)

```
GET /api/export/{locale}
```

Returns all translations for a locale, grouped by `group`:

```json
{
  "locale": "en",
  "data": {
    "auth": {
      "login": "Login",
      "logout": "Logout"
    },
    "general": {
      "welcome": "Welcome"
    }
  }
}
```

### Locales  `[auth required]`

```
GET    /api/locales         — list active locales
POST   /api/locales         — add a new locale
DELETE /api/locales/{id}    — deactivate a locale
```

## Running Tests

```bash
php artisan test
```

Or with coverage:

```bash
php artisan test --coverage
```

The test suite covers authentication, full CRUD, search/filter scenarios, export caching, and a performance assertion (export < 500ms).

## Environment Variables

Key variables to configure in `.env`:

| Variable        | Description                      |
|-----------------|----------------------------------|
| `DB_CONNECTION` | `pgsql`                          |
| `DB_HOST`       | PostgreSQL host                  |
| `DB_DATABASE`   | Database name                    |
| `CACHE_STORE`   | Set to `redis` for caching       |
| `REDIS_HOST`    | Redis host                       |
