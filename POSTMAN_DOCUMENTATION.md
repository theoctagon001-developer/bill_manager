# Postman API Documentation - Laravel Bills API

## Setup Instructions

### 1. Import Collection
1. Open Postman
2. Click **Import** button
3. Select the file: `postman/Bills_API.postman_collection.json`
4. Click **Import**

### 2. Configure Environment Variable
1. In Postman, click on the collection **"Laravel Bills API"**
2. Go to the **Variables** tab
3. Set the `base_url` variable:
   - **Local Development**: `http://localhost:8000`
   - **Production**: `https://your-render-app.onrender.com`

Alternatively, you can create a Postman Environment:
1. Click **Environments** in the left sidebar
2. Click **+** to create a new environment
3. Add variable:
   - **Variable**: `base_url`
   - **Initial Value**: `http://localhost:8000`
   - **Current Value**: `http://localhost:8000`
4. Save and select this environment

---

## API Endpoints

### 1. GET /api/bills
Retrieve all bills with pagination (ordered by date desc - newest first)

#### Request
- **Method**: `GET`
- **URL**: `{{base_url}}/api/bills`
- **Query Parameters**:
  - `per_page` (optional): Number of items per page (default: 15)

#### Example Request
```
GET http://localhost:8000/api/bills?per_page=15
```

#### Headers
```
Accept: application/json
```

#### Success Response (200 OK)
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "amount": "150.75",
            "category": "Utilities",
            "bill_account": "ACCOUNT-001",
            "due_date": "2024-03-15",
            "is_paid": false,
            "created_at": "2024-02-02T10:30:00.000000Z",
            "updated_at": "2024-02-02T10:30:00.000000Z"
        },
        {
            "id": 2,
            "amount": "250.00",
            "category": "Internet",
            "bill_account": "ACCOUNT-002",
            "due_date": "2024-03-20",
            "is_paid": true,
            "created_at": "2024-02-01T08:15:00.000000Z",
            "updated_at": "2024-02-01T08:15:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 2,
        "last_page": 1,
        "from": 1,
        "to": 2
    }
}
```

#### Response Fields
- **success**: Boolean indicating if the request was successful
- **data**: Array of bill objects
- **pagination**: Pagination metadata
  - **current_page**: Current page number
  - **per_page**: Items per page
  - **total**: Total number of bills
  - **last_page**: Last page number
  - **from**: Starting item number
  - **to**: Ending item number

---

### 2. POST /api/bills
Create a new bill

#### Request
- **Method**: `POST`
- **URL**: `{{base_url}}/api/bills`

#### Headers
```
Content-Type: application/json
Accept: application/json
```

#### Request Body (JSON)
```json
{
    "amount": 150.75,
    "category": "Utilities",
    "bill_account": "ACCOUNT-001",
    "due_date": "2024-03-15",
    "is_paid": false
}
```

#### Field Descriptions
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `amount` | decimal | Yes | Bill amount (min: 0) |
| `category` | string | Yes | Bill category (max: 255 characters) |
| `bill_account` | string | Yes | Bill account identifier (max: 255 characters) |
| `due_date` | date | Yes | Due date in YYYY-MM-DD format |
| `is_paid` | boolean | No | Payment status (default: false) |

#### Example Request Bodies

**Example 1: Unpaid Bill**
```json
{
    "amount": 150.75,
    "category": "Utilities",
    "bill_account": "ACCOUNT-001",
    "due_date": "2024-03-15",
    "is_paid": false
}
```

**Example 2: Paid Bill**
```json
{
    "amount": 250.00,
    "category": "Internet",
    "bill_account": "ACCOUNT-002",
    "due_date": "2024-03-20",
    "is_paid": true
}
```

**Example 3: Without is_paid (defaults to false)**
```json
{
    "amount": 99.99,
    "category": "Phone",
    "bill_account": "ACCOUNT-003",
    "due_date": "2024-03-25"
}
```

#### Success Response (201 Created)
```json
{
    "success": true,
    "message": "Bill created successfully",
    "data": {
        "id": 1,
        "amount": "150.75",
        "category": "Utilities",
        "bill_account": "ACCOUNT-001",
        "due_date": "2024-03-15",
        "is_paid": false,
        "created_at": "2024-02-02T10:30:00.000000Z",
        "updated_at": "2024-02-02T10:30:00.000000Z"
    }
}
```

#### Validation Error Response (422 Unprocessable Entity)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "amount": [
            "The amount field is required."
        ],
        "category": [
            "The category field is required."
        ],
        "bill_account": [
            "The bill account field is required."
        ],
        "due_date": [
            "The due date field is required.",
            "The due date must be a valid date."
        ]
    }
}
```

---

## Testing Examples

### Test Case 1: Create a Utility Bill
```bash
POST http://localhost:8000/api/bills
Content-Type: application/json

{
    "amount": 150.75,
    "category": "Utilities",
    "bill_account": "ACCOUNT-001",
    "due_date": "2024-03-15",
    "is_paid": false
}
```

### Test Case 2: Create an Internet Bill (Paid)
```bash
POST http://localhost:8000/api/bills
Content-Type: application/json

{
    "amount": 250.00,
    "category": "Internet",
    "bill_account": "ACCOUNT-002",
    "due_date": "2024-03-20",
    "is_paid": true
}
```

### Test Case 3: Get All Bills (First Page)
```bash
GET http://localhost:8000/api/bills?per_page=10
Accept: application/json
```

### Test Case 4: Get All Bills (Second Page)
```bash
GET http://localhost:8000/api/bills?per_page=10&page=2
Accept: application/json
```

---

## Common Categories
You can use these categories when creating bills:
- Utilities
- Internet
- Phone
- Rent
- Insurance
- Credit Card
- Loan
- Gas
- Water
- Electricity

---

## Error Handling

### Common Error Codes
- **200 OK**: Request successful
- **201 Created**: Resource created successfully
- **422 Unprocessable Entity**: Validation error
- **500 Internal Server Error**: Server error

### Troubleshooting

1. **Connection Refused**
   - Make sure Laravel server is running: `php artisan serve`
   - Check if the port is correct (default: 8000)

2. **404 Not Found**
   - Verify the base URL is correct
   - Ensure API routes are registered in `bootstrap/app.php`

3. **422 Validation Errors**
   - Check all required fields are present
   - Verify date format is YYYY-MM-DD
   - Ensure amount is a valid number

4. **500 Server Error**
   - Check Laravel logs: `storage/logs/laravel.log`
   - Verify database connection in `.env`
   - Ensure migrations have been run: `php artisan migrate`

---

## Running Tests

To run the PHPUnit tests:
```bash
php artisan test
```

To run specific test:
```bash
php artisan test --filter BillApiTest
```

---

## Notes

- All dates should be in `YYYY-MM-DD` format
- Amount values support 2 decimal places
- Bills are automatically ordered by `created_at` in descending order (newest first)
- Default pagination is 15 items per page
- The `is_paid` field defaults to `false` if not provided
