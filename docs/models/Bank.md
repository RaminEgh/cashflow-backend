## Bank

Represents a financial institution. Banks group multiple `Deposit` accounts and provide naming and branding metadata.

### Key Relations

- Has many `Deposit`

### Notable Behavior

- Slug automatically generated from `en_name` using Spatie Sluggable.

### Typical Fields

- `id`, `name`, `en_name`, `slug`, `logo`


