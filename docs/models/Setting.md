## Setting

Key-value configuration storage with JSON-aware getter/setter. Supports prefix filtering and helpers for get/set by key.

### Behavior

- `getValueAttribute` auto-decodes JSON where applicable.
- `setValueAttribute` serializes non-strings as JSON.
- `scopeByPrefix` filters by key prefix.
- `findByKey`, `getValue`, `setValue` convenience methods.

### Typical Fields

- `id`, `key`, `value`
