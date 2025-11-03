## Allocation

Represents a budget allocation entry for an `Organ` (organization). Allocations can be used to define how much budget is assigned to specific purposes or timeframes.

### Key Relations

- Belongs to `Organ`

### Typical Fields

- `id` – primary key
- `organ_id` – reference to organization
- Additional allocation attributes (amount, period, purpose) as defined in migrations

### Usage Notes

- Use allocations to plan budgets and compare against realized `TimelineEntry` incomes/expenses.


