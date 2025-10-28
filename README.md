<p align="center"><strong>Cashflow Backend</strong></p>

Cashflow Backend is a Laravel 12 API that powers a cashflow management system. It provides user/admin access, bank/deposit management, settings management with caching, and monthly income/expense analytics.

### Tech Stack

- PHP 8.3, Laravel 12, Sanctum (API auth)
- Pest (tests), Pint (formatter)

### Quick Start

1) Install dependencies

```bash
composer install
```

2) Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

3) Database setup

```bash
php artisan migrate --graceful
php artisan db:seed
```

4) Run the app (dev convenience script)

```bash
composer run dev
```

Or run server only:

```bash
php artisan serve
```

### Authentication

- Uses Laravel Sanctum. Obtain a token via your auth flow, then call API endpoints with `Authorization: Bearer <token>`.

### Key Features

- Settings service with cache, bulk ops, and REST API
- Admin and Organ modules with granular permissions
- Uploads service for file management
- Monthly income/expense reporting (deposit- and organ-level)

### API Overview

- Public: `GET /` health; `GET /api/test` test; `POST /api/debug`
- Authenticated: `GET /api/user`
- Admin: `GET /api/admin/...` users, roles, access, banks, deposits, permissions, monthly-income-expense
- Organ: `GET /api/organ/...` banks, deposits
- Settings: `GET/POST/DELETE /api/settings/...`
- Uploads: `GET/POST/DELETE /api/upload/...`

See detailed docs:

- docs/API.md — endpoints and request/response samples
- docs/SETTING_SERVICE.md — settings service
- docs/MONTHLY_INCOME_EXPENSE.md — calculations and commands
- docs/SETTING_SERVICE_EXAMPLES.md — usage examples
- docs/ARCHITECTURE.md — project structure

### Testing

```bash
php artisan test
```

Run a single file or filter when iterating:

```bash
php artisan test tests/Feature/SettingControllerTest.php
php artisan test --filter=ParsianBankAdapterTest
```

### Coding Standards

```bash
vendor/bin/pint --dirty
```

### License

This project is licensed under the MIT license.
