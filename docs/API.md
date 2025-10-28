# API Documentation

This document summarizes the public API surface. All routes below are prefixed with `/api` unless otherwise noted. Authentication uses Sanctum bearer tokens unless marked Public.

## Auth & Health

- Public: `GET /` (web) returns Laravel version
- Public: `GET /api/test` sample response
- Public: `POST /api/debug` sample response
- Authenticated: `GET /api/user` current user

## Settings (Authenticated)

Base: `/api/settings`

- `GET /` get all
- `GET /get?key=app.name` get one
- `POST /set` body: `{ key, value }`
- `POST /get-multiple` body: `{ keys: string[] }`
- `POST /set-multiple` body: `{ settings: Record<string, mixed> }`
- `GET /has?key=...`
- `DELETE /delete?key=...`
- `GET /by-prefix?prefix=app.`
- `DELETE /by-prefix?prefix=app.`
- `POST /clear-cache`

See `docs/SETTING_SERVICE.md` for details and examples.

## Uploads (Authenticated)

Base: `/api/upload`

- `GET /` list
- `GET /{upload}` show
- `POST /` create
- `GET /{upload}/download` download
- `DELETE /{upload}` delete

## Admin (Authenticated + is-admin + permissions)

Base: `/api/admin`

### Monthly Income / Expense

Base: `/api/admin/monthly-income-expense`

- Deposits:
  - `GET /deposits`
  - `GET /deposits/{depositId}/months`
  - `GET /deposits/{depositId}` query: `year_month=YYYY-MM`
  - `GET /deposits/{depositId}/yearly-summary` query: `year=YYYY`
  - `GET /deposits/{depositId}/detailed-changes` query: `year_month=YYYY-MM`
  - `GET /all-deposits` query: `year_month=YYYY-MM`
- Organs:
  - `GET /organs`
  - `GET /organs/{organId}/months`
  - `GET /organs/{organId}` query: `year_month=YYYY-MM`
  - `GET /organs/{organId}/yearly-summary` query: `year=YYYY`
  - `GET /all-organs` query: `year_month=YYYY-MM`

See `docs/MONTHLY_INCOME_EXPENSE.md` for responses and logic.

### Users

Base: `/api/admin/user`

- `GET /` list (permission: USER_LIST)
- `GET /status` statuses (permission: USER_SHOW)
- `GET /{user}` show (permission: USER_SHOW)
- `POST /` create (permission: USER_CREATE)
- `PUT /{user}` update (permission: USER_EDIT)
- `DELETE /{user}` delete (permission: USER_DELETE)
- `PATCH /{user}/block` (permission: USER_BLOCK)
- `PATCH /{user}/unblock` (permission: USER_UNBLOCK)

### Organs

Base: `/api/admin/organ`

- `GET /` list (ORGAN_LIST)
- `GET /{organ}` show (ORGAN_SHOW)
- `POST /` create (ORGAN_CREATE)
- `PUT /{organ}` update (ORGAN_EDIT)
- `DELETE /{organ}` delete (ORGAN_DELETE)
- `PATCH /{organ}/assign` assign admin (ORGAN_ASSIGN_ADMIN)
- `GET /{organ}/allocation` allocation (ORGAN_SHOW)

### Deposits

Base: `/api/admin/deposit`

- `GET /` list (ORGAN_LIST)
- `GET /{deposit}` show (ORGAN_SHOW)
- `POST /` create (ORGAN_CREATE)
- `PUT /{deposit}` update (ORGAN_EDIT)
- `DELETE /{deposit}` delete (ORGAN_DELETE)

### Banks

Base: `/api/admin/bank`

- `GET /` list (BANK_LIST)
- `GET /{bank}` show (BANK_SHOW)
- `POST /` create (BANK_CREATE)
- `PUT /{bank}` update (BANK_EDIT)
- `DELETE /{bank}` delete (BANK_DELETE)

### Admins

Base: `/api/admin/admin`

- `GET /` list (ADMIN_ADMIN_LIST)
- `POST /` create (ADMIN_ADMIN_CREATE)
- `GET /{user}` show (ADMIN_ADMIN_SHOW)
- `PUT /{user}` update (ADMIN_ADMIN_EDIT)
- `DELETE /{user}` delete (ADMIN_ADMIN_DELETE)

### Permissions

Base: `/api/admin/permission`

- `GET /` list (PERMISSION_LIST)
- `GET /{permission}` show (PERMISSION_SHOW)
- `PUT /{permission}` update (PERMISSION_EDIT)

### Roles

Base: `/api/admin/role`

- `GET /` list (ROLE_LIST)
- `POST /` create (ROLE_CREATE)
- `GET /{role}` show (ROLE_SHOW)
- `PUT /{role}` update (ROLE_EDIT)

### Access

Base: `/api/admin/access`

- `GET /` list (ACCESS_LIST)
- `GET /{user}` show (ACCESS_LIST)
- `PUT /{user}` update (ACCESS_ASSIGN)

## Organ (Authenticated + is-organ + permissions)

Base: `/api/organ`

### Banks

Base: `/api/organ/bank`

- `GET /` list (BANK_LIST)
- `GET /{bank}` show (BANK_SHOW)
- `POST /` create (BANK_CREATE)
- `PUT /{bank}` update (BANK_EDIT)
- `DELETE /{bank}` delete (BANK_DELETE)

### Deposits

Base: `/api/organ/deposit`

- `GET /` list (DEPOSIT_LIST)
- `GET /types` types (DEPOSIT_LIST)
- `GET /{deposit}` show (DEPOSIT_SHOW)
- `POST /` create (DEPOSIT_CREATE)
- `PUT /{deposit}` update (DEPOSIT_EDIT)
- `DELETE /{deposit}` delete (DEPOSIT_DELETE)

## Mock Endpoints (Public - for testing only)

Base: `/api/mock`

- `POST /saman` returns `{ balance }`
- `POST /mellat` returns `{ balance }`
- `POST /parsian` returns `{ balance }`

## Authentication

Use Sanctum tokens. Include header:

```http
Authorization: Bearer <token>
```

## Error Format

Most endpoints return a JSON wrapper via `App\\Helpers\\Helper::successResponse`. Typical shape:

```json
{
  "success": true,
  "message": "...",
  "data": {}
}
```

Errors follow Laravel validation and exception responses.
