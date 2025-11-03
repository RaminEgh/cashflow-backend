## User

Application user supporting roles, permissions, and Sanctum API tokens. Includes derived name, type, and status helpers.

### Key Relations

- Belongs to many `Role`
- Belongs to many `Organ` (as admin via pivot)
- Has many `UserSession`
- Has many `Upload`

### Notable Behavior

- Helpers: `hasRole`, `permissions`, `hasPermission` with caching
- Appended `name` virtual attribute
- Casts: `email_verified_at`, `password`

### Constants

- Types: `admin`, `organ`, `general` (+ `unknown`)
- Statuses: `active`, `inactive`, `blocked`


