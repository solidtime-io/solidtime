# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Solidtime is an open-source time tracking SaaS application built with **Laravel 12** (PHP 8.3) and **Vue 3** (TypeScript). It uses PostgreSQL, Inertia.js for server-driven SPA rendering, and Filament for the admin panel.

## Common Commands

### Backend (PHP)

```bash
composer test              # Run PHPUnit tests (--stop-on-failure)
composer ptest             # Run tests in parallel
composer analyse           # PHPStan static analysis (Level 7)
composer fix               # Laravel Pint code formatting

# Single test file
php artisan test tests/Unit/SomeTest.php

# Single test method
php artisan test tests/Unit/SomeTest.php --filter=testMethodName

# Generate IDE helpers after model changes
composer ide-helper
```

### Frontend (JavaScript/TypeScript)

```bash
npm run dev                # Vite dev server
npm run build              # Production build
npm run lint:fix           # ESLint auto-fix
npm run format             # Prettier auto-format
npm run type-check         # vue-tsc type checking
npm run test:e2e           # Playwright E2E tests
npm run zod:generate       # Regenerate API client from OpenAPI spec (requires running app on port 80)

# Single E2E test
npx playwright test e2e/calendar.spec.ts
```

### Database

```bash
php artisan migrate --seed          # Run migrations with seeders
composer refresh-schema-dump        # Dump test DB schema after migration changes
```

## Architecture

### Multi-Tenancy Model

Everything is scoped to an **Organization** (extends Jetstream Team). Users connect to organizations via **Member** (pivot with role + billable_rate). The org hierarchy:

```
Organization
├── Members (User + Role + billable_rate)
├── Projects → Tasks → TimeEntries
├── Clients (linked to Projects)
├── Tags (stored as JSON array on TimeEntry)
└── Reports
```

### Backend Layers

- **Controllers** (`app/Http/Controllers/Api/V1/`): API controllers extend a base that provides `checkPermission()`, `checkAnyPermission()` authorization helpers
- **Services** (`app/Service/`): Business logic — `BillableRateService`, `TimeEntryAggregationService`, `DashboardService`, `TimeEntryFilter`, `PermissionStore`
- **Models** (`app/Models/`): Eloquent models with UUID primary keys, computed attributes (`billable_rate`, `spent_time`), and audit trails
- **Resources** (`app/Http/Resources/V1/`): API response transformation
- **Form Requests** (`app/Http/Requests/V1/`): Input validation
- **Enums** (`app/Enums/`): Role, TimeEntryAggregationType, Weekday, etc.

### Frontend Layers

- **Pages** (`resources/js/Pages/`): Inertia page components (Time, Calendar, Timesheet, Reporting, Projects, etc.)
- **Components** (`resources/js/Components/`): Reusable Vue components
- **Packages** (`resources/js/packages/`): Internal packages for `ui` and `api`
- **Stores** (`resources/js/utils/`): Pinia stores for app state
- **Types** (`resources/js/types/`): TypeScript definitions (also auto-generated from PHP models via `composer generate-typescript`)

State management uses **Pinia** stores + **TanStack Vue Query** for server state caching.

### API Routing

All API routes under `/api/v1/organizations/{organization}/[resource]`. Two auth strategies:
- `auth:web` — session-based (Inertia pages)
- `auth:api` — OAuth2 via Passport (API clients)

### Extension System

Modular extensions in `extensions/` using nwidart/laravel-modules. Extensions can add routes, models, and pages. Current extensions: Billing, Invoicing, Services.

### Authorization

Custom permission system via `PermissionStore` service with roles: Owner, Admin, Manager, Employee, Placeholder. Premium features gated by billing/trial status. `CheckOrganizationBlocked` middleware prevents operations on blocked orgs.

### Billable Rate Resolution

Rates cascade with specificity: TimeEntry > Project > Member > Organization. Rates stored in cents (integers). Computed by `BillableRateService`.

## Code Quality Standards

- **PHP**: Strict types enforced (`declare_strict_types`), strict comparisons, PHPStan Level 7, Laravel Pint preset
- **TypeScript/Vue**: ESLint + Prettier (100 char width, 4-space indent, single quotes, trailing commas ES5)
- **Indentation**: 4 spaces everywhere (2 spaces for YAML)
- **All models use UUIDs** as primary keys

## Testing

- **PHP tests**: `tests/Unit/` and `tests/Feature/` + extension tests in `extensions/*/tests/`
- **E2E tests**: `e2e/` directory with Playwright
- Tests use a separate PostgreSQL test database with schema dump for speed
- Factories in `database/factories/` for test data generation

## Contributing

- Must open issue/discussion and get approval before submitting PRs (except tiny fixes)
- CLA signature required
- Use GitHub keywords in PRs ("Closes #123", "Fixes #123")
