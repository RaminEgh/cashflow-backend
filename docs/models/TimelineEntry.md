## TimelineEntry

Represents a single cashflow movement (income or expense) on a specific date for an organization.

### Key Relations

- Belongs to `Organ`

### Notable Constants & Casts

- Types: `income`, `expense`
- Casts: `date` to Date, `amount` to decimal(2)

### Typical Fields

- `id`, `organ_id`, `type`, `title`, `date`, `amount`

### Usage Notes

- Used to build timelines and summaries of incomes/outgoings.


