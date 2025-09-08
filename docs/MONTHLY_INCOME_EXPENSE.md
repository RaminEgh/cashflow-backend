# Monthly Income and Expense Calculation

This feature calculates monthly income and expenses for rahkaran accounts based on balance changes tracked in the `balances` table.

## How It Works

1. **Balance Tracking**: The system fetches rahkaran balances via API and stores them in the `balances` table
2. **Monthly Calculation**: For each month, the system:
   - Gets the balance at the start of the month (last balance before month starts)
   - Gets the balance at the end of the month (last balance in the month)
   - Analyzes all balance changes during the month
   - Calculates income (positive changes) and expenses (negative changes)

## API Endpoints

### Deposit-Level Endpoints

#### 1. Get Available Deposits
```
GET /api/monthly-income-expense/deposits
```
Returns all deposits available for income/expense calculations.

#### 2. Get Available Months for a Deposit
```
GET /api/monthly-income-expense/deposits/{depositId}/months
```
Returns all months that have balance data for a specific deposit.

#### 3. Get Monthly Income/Expense for a Deposit
```
GET /api/monthly-income-expense/deposits/{depositId}?year_month=2024-01
```
Returns monthly income and expense calculation for a specific deposit and month.

**Response:**
```json
{
  "success": true,
  "message": "Monthly income and expenses calculated successfully",
  "data": {
    "deposit_id": 1,
    "year_month": "2024-01",
    "start_balance": 1000000,
    "end_balance": 1200000,
    "income": 300000,
    "expenses": 100000,
    "net_change": 200000,
    "balance_records_count": 5,
    "deposit": {
      "id": 1,
      "number": "1234567890",
      "organ": {...},
      "bank": {...}
    }
  }
}
```

#### 4. Get Yearly Summary for a Deposit
```
GET /api/monthly-income-expense/deposits/{depositId}/yearly-summary?year=2024
```
Returns yearly summary with monthly breakdown.

#### 5. Get Detailed Monthly Changes
```
GET /api/monthly-income-expense/deposits/{depositId}/detailed-changes?year_month=2024-01
```
Returns detailed balance changes for each day in the month.

#### 6. Get All Deposits Monthly Summary
```
GET /api/monthly-income-expense/all-deposits?year_month=2024-01
```
Returns monthly income/expense for all deposits.

### Organ-Level Endpoints

#### 1. Get Available Organs
```
GET /api/monthly-income-expense/organs
```
Returns all organs available for income/expense calculations.

#### 2. Get Available Months for an Organ
```
GET /api/monthly-income-expense/organs/{organId}/months
```
Returns all months that have balance data for any deposit of a specific organ.

#### 3. Get Monthly Income/Expense for an Organ
```
GET /api/monthly-income-expense/organs/{organId}?year_month=2024-01
```
Returns monthly income and expense calculation for all deposits of a specific organ.

**Response:**
```json
{
  "success": true,
  "message": "Organ monthly income and expenses calculated successfully",
  "data": {
    "organ_id": 1,
    "organ": {
      "id": 1,
      "name": "Organization Name",
      "deposits": [...]
    },
    "year_month": "2024-01",
    "total_income": 5000000,
    "total_expenses": 2000000,
    "total_net_change": 3000000,
    "deposits_count": 5,
    "deposits_data": [
      {
        "deposit_id": 1,
        "year_month": "2024-01",
        "start_balance": 1000000,
        "end_balance": 1200000,
        "income": 300000,
        "expenses": 100000,
        "net_change": 200000,
        "deposit": {...}
      }
    ]
  }
}
```

#### 4. Get Yearly Summary for an Organ
```
GET /api/monthly-income-expense/organs/{organId}/yearly-summary?year=2024
```
Returns yearly summary with monthly breakdown for all deposits of an organ.

#### 5. Get All Organs Monthly Summary
```
GET /api/monthly-income-expense/all-organs?year_month=2024-01
```
Returns monthly income/expense for all organs.

## Console Commands

### Deposit-Level Commands

#### 1. Calculate for Specific Deposit
```bash
php artisan app:calculate-monthly-income-expense {deposit_id} {year_month}
```
Example:
```bash
php artisan app:calculate-monthly-income-expense 1 2024-01
```

#### 2. Calculate for All Deposits
```bash
php artisan app:calculate-monthly-income-expense --all {year_month}
```
Example:
```bash
php artisan app:calculate-monthly-income-expense --all 2024-01
```

#### 3. Calculate Yearly Summary
```bash
php artisan app:calculate-monthly-income-expense {deposit_id} {year_month} --yearly
```
Example:
```bash
php artisan app:calculate-monthly-income-expense 1 2024-01 --yearly
```

### Organ-Level Commands

#### 4. Calculate for Specific Organ
```bash
php artisan app:calculate-monthly-income-expense --organ={organ_id} {year_month}
```
Example:
```bash
php artisan app:calculate-monthly-income-expense --organ=1 2024-01
```

#### 5. Calculate Organ Yearly Summary
```bash
php artisan app:calculate-monthly-income-expense --organ={organ_id} {year_month} --yearly
```
Example:
```bash
php artisan app:calculate-monthly-income-expense --organ=1 2024-01 --yearly
```

#### 6. Calculate for All Organs
```bash
php artisan app:calculate-monthly-income-expense --all-organs {year_month}
```
Example:
```bash
php artisan app:calculate-monthly-income-expense --all-organs 2024-01
```

#### 7. Show Available Options
```bash
php artisan app:calculate-monthly-income-expense
```
Shows all available deposits, organs, and command options.

## Calculation Logic

### Income Calculation
- Income = Sum of all positive balance changes during the month
- A positive change occurs when current balance > previous balance

### Expense Calculation
- Expenses = Sum of all negative balance changes during the month
- A negative change occurs when current balance < previous balance

### Organ-Level Aggregation
- **Total Income**: Sum of income from all deposits of the organ
- **Total Expenses**: Sum of expenses from all deposits of the organ
- **Total Net Change**: Sum of net changes from all deposits of the organ

### Example
```
Organ: "کترینگ دنا"
Deposits: 3 accounts

Deposit 1:
- Start Balance: 1,000,000
- End Balance: 1,200,000
- Income: +200,000

Deposit 2:
- Start Balance: 500,000
- End Balance: 400,000
- Expenses: -100,000

Deposit 3:
- Start Balance: 800,000
- End Balance: 900,000
- Income: +100,000

Organ Totals:
- Total Income: 300,000
- Total Expenses: 100,000
- Total Net Change: +200,000
```

## Database Schema

The calculations are based on the `balances` table:

```sql
CREATE TABLE balances (
    id BIGINT PRIMARY KEY,
    deposit_id BIGINT,
    fetched_at TIMESTAMP,
    rahkaran_fetched_at TIMESTAMP NULL,
    status ENUM('fail', 'success'),
    rahkaran_status ENUM('fail', 'success'),
    balance BIGINT NULL,
    rahkaran_balance BIGINT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Requirements

1. **Balance Data**: The system needs regular balance data from rahkaran API
2. **Successful Status**: Only balances with `rahkaran_status = 'success'` are used
3. **Valid Balance**: Only balances with non-null `rahkaran_balance` are considered

## Error Handling

- If no balance data exists for a month, income and expenses will be 0
- If deposit/organ doesn't exist, returns 404 error
- If invalid year-month format, returns validation error

## Performance Considerations

- The service uses efficient database queries with proper indexing
- For large datasets, consider implementing caching
- Yearly calculations process 12 months sequentially
- Organ-level calculations aggregate data from multiple deposits
