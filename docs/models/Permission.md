## Permission

Defines a granular access capability (via `slug`) that can be attached to `Role`s. Supports hierarchical permissions via `parent_id`.

### Key Relations

- Belongs to many `Role`
- Has many child `Permission`

### Typical Fields

- `id`, `slug`, `label`, `description`, `parent_id`


