## Organ (Organization)

Top-level entity representing an organization whose cashflow is managed.

### Key Relations

- Belongs to many `User` as admins (pivot `organ_admin`)
- Has many `Deposit`
- Has many `Allocation`
- Has many `TimelineEntry`

### Notable Behavior

- Slug automatically generated from `en_name` using Spatie Sluggable.

### Typical Fields

- `id`, `name`, `en_name`, `slug`, contact and metadata fields


