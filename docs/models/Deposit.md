## Deposit

Represents a bank account for an `Organ` at a `Bank`. Supports multiple deposit types (current, saving, investment, etc.).

### Key Relations

- Belongs to `Organ`
- Belongs to `Bank`
- Has many `Balance`

### Notable Constants

- `DEPOSIT_TYPES` and `DEPOSITS_KEY_VALUE` define supported account types and their labels.

### Typical Fields

- `id`, `organ_id`, `bank_id`, `type`, `number`, `title`

### Usage Notes

- Use `Deposit` as the anchor entity for cash balances and movement analytics.


