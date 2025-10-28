# Architecture Overview

This document explains the structure and main components of the Cashflow Backend.

## Framework & Versions

- PHP 8.3
- Laravel 12 (streamlined structure)
- Sanctum for API authentication
- Pest for tests, Pint for code style

## Directory Structure (key paths)

- `app/`
  - `Console/Commands/` domain commands (e.g., income/expense calculators)
  - `Constants/` permission keys and cache keys
  - `Events/` login/logout events
  - `Listeners/` event listeners for auth session tracking
  - `Facades/` `Setting` facade
  - `Helpers/` `Helper` with response helpers and shortcuts
  - `Http/`
    - `Controllers/` REST controllers (Admin, Organ, Upload, Settings, MonthlyIncomeExpense)
    - `Middleware/` auth/role middleware
    - `Requests/` form requests (validation)
    - `Resources/` API resources/transformers
  - `Jobs/` background tasks (e.g., `FetchBankAccountBalance`)
  - `Models/` Eloquent models (User, Bank, Deposit, Balance, Role/Permission, Setting, Upload, Organ)
  - `Policies/` authorization policies for key models
  - `Providers/` service providers (App, Event, Setting, Banking)
  - `Services/`
    - `Banking/` adapters and banking services
    - `Rahkaran/` integration services
    - `SettingService.php` central settings service
- `routes/`
  - `api.php` base API, auth grouping, uploads, settings, timeline
  - `admin.php` admin domain routes
  - `organ.php` organ domain routes
  - `web.php` health endpoint
- `database/` factories, migrations, seeders
- `tests/` Pest tests (Feature/Unit)

## Authentication & Authorization

- Sanctum provides token-based auth for API consumers
- Admin vs Organ separation via middleware: `is-admin`, `is-organ`
- Fine-grained permissions using constants: `AdminPermissionKey`, `OrganPermissionKey`
- Policies enforce model-level authorization

## Settings Service

- Centralized configuration storage with caching and type-flexible values
- Exposed via service class, facade, helper, and REST API
- See `docs/SETTING_SERVICE.md` and `docs/SETTING_SERVICE_EXAMPLES.md`

## Banking & Rahkaran

- `Services/Banking` contains adapters (e.g., Parsian) and banking abstractions
- `Jobs/FetchBankAccountBalance` retrieves and persists balances
- `Services/Rahkaran` handles domain-specific Rahkaran integrations

## Monthly Income/Expense Analytics

- Controller: `MonthlyIncomeExpenseController`
- Admin routes under `/api/admin/monthly-income-expense`
- Computes start/end balances per month, aggregates income/expenses, and yearly summaries
- See `docs/MONTHLY_INCOME_EXPENSE.md`

## Uploads

- `UploadController` for file management: list/show/store/download/delete
- Files stored under `storage/app/public` by default (per Laravel configuration)

## Events & Listeners

- `LoginEvent`, `LogoutEvent` emitted on auth changes
- `LoginListener`, `LogoutListener` persist session tracking in `UserSession`

## Caching & Performance

- Settings cached with configurable TTL
- Prefer eager loading on resources to prevent N+1 queries
- Use route middleware for authz checks to reduce controller branching

## Testing Strategy

- Feature tests for controllers and flows (e.g., settings, parsian adapter, timeline)
- Unit tests for services (e.g., `SettingServiceTest`)
- Use factories and seeders; prefer minimal test scopes using file or filter

## Coding Standards

- Follow Laravel conventions and this project’s style
- Format with Pint: `vendor/bin/pint --dirty`
- Explicit types and PHP 8 constructor property promotion

## Environments & Scripts

- Composer scripts:
  - `composer run dev` runs server, queue listener, and vite (if used)
  - `composer test` clears config cache and runs tests
- Typical bootstrap:
  - `cp .env.example .env && php artisan key:generate`
  - `php artisan migrate --graceful && php artisan db:seed`

## Routes Summary

- See `docs/API.md` for detailed endpoint list and parameters.
