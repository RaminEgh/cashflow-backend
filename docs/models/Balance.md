## Balance

Stores point-in-time balance snapshots for a `Deposit` (bank account). Useful for historical tracking and analytics.

### Key Relations

- Belongs to `Deposit`

### Typical Fields

- `id`
- `deposit_id`
- `amount`
- `captured_at` (timestamp)

### Usage Notes

- Pair with `Deposit` to build monthly income/expense or trend charts.


