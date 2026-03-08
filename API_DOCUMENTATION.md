# API Documentation (A–Z)

Base URL: Replace with your deployment URL, e.g. `https://your-domain.com`

- Content-Type: application/json unless noted
- Monetary values are returned as strings with two decimals
- Dates use `YYYY-MM-DD`
- Pagination keys: `current_page`, `per_page`, `total`, `last_page`, `from`, `to`

---

## Bills

### GET /api/bills
- Query:
  - `per_page` number (default 15)
  - `bill_account` string
  - `category` string
  - `is_paid` boolean
  - `month` string (format `YYYY-MM` or `MM-YYYY`)
  - `months_count` number (default 12; used when `month` is not provided)
- Response (200):
```json
{
  "success": true,
  "filters": {
    "bill_account": "ACCOUNT-001",
    "months_count": 12,
    "category": "Internet",
    "is_paid": false,
    "month": "2026-04"
  },
  "data": [
    {
      "bill_account": "ACCOUNT-001",
      "summary": {
        "total_bills": 5,
        "total_amount": "1500.00",
        "total_paid": "900.00",
        "total_cleared": "500.00",
        "count_paid": 3,
        "count_cleared": 2
      },
      "months": [
        {
          "month": "April-2026",
          "bills": [
            {
              "id": 73,
              "amount": "300.00",
              "category": "Internet",
              "bill_account": "ACCOUNT-001",
              "due_date": "2026-04-15",
              "is_paid": false,
              "is_cleared": false,
              "image": "https://your-domain.com/storage/bills/73.png"
            }
          ]
        }
      ]
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 14,
    "last_page": 1,
    "from": 1,
    "to": 14
  }
}
```

### GET /api/bills/{id}
- Path:
  - `id` integer
- Response (200):
```json
{
  "success": true,
  "data": {
    "id": 73,
    "amount": "372.72",
    "category": "Internet",
    "bill_account": "ACCOUNT-2272",
    "due_date": "2026-02-17",
    "is_paid": false,
    "is_cleared": false,
    "image": "https://your-domain.com/storage/bills/73.png"
  }
}
```

### POST /api/bills
- Body:
```json
{
  "amount": 3500,
  "category": "WiFi",
  "bill_account": "Apa Seema",
  "due_date": "2026-02-15",
  "is_paid": false,
  "image": "data:image/png;base64,iVBORw0KGgoAAA..."
}
```
- Notes:
  - If sending multipart/form-data, set `image` as a file; server converts to base64 and stores in DB
  - If sending a base64/data URL string, server saves as-is in DB
- Response (201):
```json
{
  "success": true,
  "message": "Bill created successfully",
  "data": {
    "id": 73,
    "amount": "3500.00",
    "category": "WiFi",
    "bill_account": "Apa Seema",
    "due_date": "2026-02-15",
    "is_paid": false,
    "is_cleared": false,
    "image": "https://your-domain.com/storage/bills/73.png"
  }
}
```

### PUT/PATCH /api/bills/{id}
- Body: Partial update; same fields as POST
- Response (200):
```json
{
  "success": true,
  "message": "Bill updated successfully",
  "data": {
    "id": 73,
    "amount": "4000.00",
    "category": "Internet",
    "bill_account": "New Account Name",
    "due_date": "2026-03-01",
    "is_paid": true,
    "is_cleared": false,
    "image": "https://your-domain.com/storage/bills/73.png"
  }
}
```

### DELETE /api/bills/{id}
- Behavior: permanent deletion
- Response (200):
```json
{ "success": true, "message": "Bill deleted successfully" }
```

### POST /api/bills/{id}/image
- Body:
  - `image`: File (multipart/form-data) or base64/data URL string
- Response (200):
```json
{
  "success": true,
  "message": "Bill image updated successfully",
  "image_url": "https://your-domain.com/storage/bills/73.png"
}
```

### DELETE /api/bills/{id}/image
- Clears generated file and DB image
- Response (200):
```json
{ "success": true, "message": "Bill image deleted successfully" }
```

### GET /api/bills/{id}/image/download
- Regenerates file from DB content if missing, serves the image
- Response: binary file download

### GET /api/bills/dashboard
- Query:
  - `month` integer (optional; defaults to current)
  - `year` integer (optional; defaults to current)
- Response (200):
```json
{
  "success": true,
  "filters": { "month": 4, "year": 2026 },
  "summary": {
    "total_amount_to_be_paid": "1200.00",
    "total_paid": "800.00",
    "total_bills": 6,
    "total_paid_bills": 4,
    "unpaid_count": 2
  },
  "data": [
    {
      "id": 91,
      "amount": "200.00",
      "category": "Electricity",
      "bill_account": "ACCOUNT-ABC",
      "due_date": "2026-04-10",
      "is_paid": false,
      "is_cleared": false,
      "image": "https://your-domain.com/storage/bills/91.png"
    }
  ]
}
```

---

## Budgets

### POST /api/budgets
- Body:
```json
{ "amount": 5000, "month": 4, "year": 2026, "expense": 1500 }
```
- Response (201):
```json
{
  "success": true,
  "message": "Budget created successfully",
  "data": {
    "id": 12,
    "month": 4,
    "year": 2026,
    "budget": "5000.00",
    "expense_assumed": "1500.00",
    "saving_assumed": "3500.00",
    "summary": {
      "actual_expense": "1200.00",
      "expense_from_saving": "0.00",
      "does_exceeded_the_budget": false
    },
    "expenses": {
      "categories": [
        { "category": "food", "total_expense": "800.00", "count": 5 },
        { "category": "travel", "total_expense": "400.00", "count": 2 }
      ],
      "items": [
        { "id": 101, "amount": "50.00", "category": "food", "spend_date": "2026-04-02", "month": 4, "year": 2026 }
      ]
    }
  }
}
```

### GET /api/budgets
- Query: `per_page` number
- Response (200): paginated array of budget objects (shape above)

### GET /api/budgets/search?month=4&year=2026
- Response (200): single budget object (shape above)

### GET /api/budgets/{id}
- Response (200): single budget object (shape above)

### PUT/PATCH /api/budgets/{id}
- Body: `amount`, `month`, `year`, `expense` (optional)
- Response (200): updated budget object (shape above)

### DELETE /api/budgets/{id}
- Behavior: permanent deletion; expenses NOT deleted
- Response (200):
```json
{ "success": true, "message": "Budget deleted successfully" }
```

---

## Expenses

### POST /api/expenses
- Body:
```json
{
  "amount": 250.75,
  "category": "travel",
  "spend_date": "2026-03-10",
  "month": 3,
  "year": 2026,
  "budget_id": 12
}
```
- Response (201):
```json
{
  "success": true,
  "message": "Expense recorded successfully",
  "data": {
    "id": 501,
    "amount": "250.75",
    "category": "travel",
    "spend_date": "2026-03-10",
    "month": 3,
    "year": 2026,
    "budget_id": 12
  }
}
```

### GET /api/expenses
- Query: `per_page`, `month`, `year`, `category`
- Response (200):
```json
{
  "success": true,
  "data": [
    { "id": 1, "amount": "100.00", "category": "food", "spend_date": "2026-01-01", "month": 1, "year": 2026, "budget_id": 12 }
  ],
  "grouped": [
    { "category": "food", "total_expense": "150.00", "count": 2 },
    { "category": "travel", "total_expense": "80.00", "count": 1 }
  ],
  "pagination": { "current_page": 1, "per_page": 10, "total": 3, "last_page": 1, "from": 1, "to": 3 }
}
```

### GET /api/expenses/search?month=4&year=2026&per_page=10
- Response (200): same shape as GET /api/expenses, filtered by month/year

### GET /api/expenses/{id}
- Response (200):
```json
{
  "success": true,
  "data": {
    "id": 501,
    "amount": "250.75",
    "category": "travel",
    "spend_date": "2026-03-10",
    "month": 3,
    "year": 2026,
    "budget_id": 12
  }
}
```

### PUT/PATCH /api/expenses/{id}
- Body: partial update for any fields
- Response (200): updated expense (shape above)

### DELETE /api/expenses/{id}
- Behavior: permanent deletion
- Response (200):
```json
{ "success": true, "message": "Expense deleted successfully" }
```

---

## Savings

### GET /api/savings
- Query:
  - `start_month` string `YYYY-MM` optional
  - `end_month` string `YYYY-MM` optional
- Response (200):
```json
{
  "success": true,
  "global": {
    "Total_Amount": "8500.00",
    "Total_Expense_Assumed": "3000.00",
    "Total_Saving_Assumed": "5500.00",
    "Total_Actual_Saving": "4200.00"
  },
  "range": {
    "start_month": "2026-03",
    "end_month": "2026-04"
  },
  "details": [
    {
      "year": 2026,
      "month": 3,
      "Total_Amount": "3000.00",
      "Total_Expense_Assumed": "1000.00",
      "Total_Saving_Assumed": "2000.00",
      "Total_Actual_Saving": "2400.00"
    },
    {
      "year": 2026,
      "month": 4,
      "Total_Amount": "5500.00",
      "Total_Expense_Assumed": "2000.00",
      "Total_Saving_Assumed": "3500.00",
      "Total_Actual_Saving": "1800.00"
    }
  ]
}
```
- If no date range provided, only `global` is returned.

---

## Dashboard (Budget + Expense)

### GET /api/dashboard
- Query:
  - `month` integer optional (defaults to current)
  - `year` integer optional (defaults to current)
- Response (200):
```json
{
  "success": true,
  "filters": { "month": 4, "year": 2026 },
  "data": {
    "salary": "5000.00",
    "expense": { "assumption": "1500.00", "real": "1200.00" },
    "saving": { "assumption": "3500.00", "real": "3800.00" },
    "limit_exceeded": false,
    "expense_from_savings": "0.00",
    "remaining_balance": "3800.00",
    "budget_usage": [
      {
        "name": "food",
        "spend": "800.00",
        "records": [
          { "id": 101, "amount": "50.00", "spend_date": "2026-04-02", "category": "food" }
        ]
      }
    ]
  }
}
```

---

## Error Responses

### 404 Not Found
```json
{ "success": false, "message": "Resource not found" }
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field": [ "Validation message..." ]
  }
}
```

### 500 Internal Server Error
```json
{ "success": false, "message": "Server error", "error": "..." }
```

---

## Image Handling (Bills)
- On create/update, the image is saved in the database (base64/data URL).
- On any GET that returns a bill, if `/storage/bills/{id}.png` is missing but the DB has image content, the server generates the file and returns the network link.
- If the file already exists, the server does not rewrite it (saves time).
- This addresses free server cooldowns that flush ephemeral storage.

---

## Delete Semantics
- Bills, budgets, expenses: permanent delete on DELETE.
- Deleting a budget does NOT delete expenses; expenses remain with `budget_id` set to null.

