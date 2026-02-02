# API Testing Guide

## Quick Start

### Running Tests
```bash
# Run all tests
php artisan test

# Run only Bill API tests
php artisan test --filter BillApiTest

# Run with verbose output
php artisan test --filter BillApiTest -v
```

## Test Cases Created

### 1. `test_can_create_bill()`
- Tests successful bill creation via POST endpoint
- Validates response structure and status code (201)
- Verifies data is stored in database

### 2. `test_create_bill_validation_errors()`
- Tests validation when required fields are missing
- Verifies 422 status code
- Checks validation error messages

### 3. `test_can_get_bills_with_pagination()`
- Tests GET endpoint with pagination
- Creates 20 test bills
- Verifies pagination metadata

### 4. `test_bills_ordered_by_date_desc()`
- Tests that bills are returned in descending order (newest first)
- Creates bills with different timestamps
- Verifies ordering

### 5. `test_can_create_bill_with_is_paid_true()`
- Tests creating a bill with `is_paid` set to `true`
- Verifies the value is correctly stored

## Test Data

The tests use the `BillFactory` which generates random test data:
- **Amount**: Random decimal between 10 and 1000
- **Category**: Random from predefined list (Utilities, Internet, Phone, etc.)
- **Bill Account**: Format: `ACCOUNT-####` (random numbers)
- **Due Date**: Random date within next 30 days
- **Is Paid**: 30% chance of being true

## Postman Collection

### Import Instructions
1. Open Postman
2. Click **Import**
3. Select `postman/Bills_API.postman_collection.json`
4. Set `base_url` variable to your server URL

### Collection Includes
- **GET /api/bills** - Retrieve all bills with pagination
- **POST /api/bills** - Create a new bill
- **POST /api/bills** (Paid) - Example with is_paid=true

## Manual Testing Examples

### Using cURL

**Create a Bill:**
```bash
curl -X POST http://localhost:8000/api/bills \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "amount": 150.75,
    "category": "Utilities",
    "bill_account": "ACCOUNT-001",
    "due_date": "2024-03-15",
    "is_paid": false
  }'
```

**Get All Bills:**
```bash
curl -X GET "http://localhost:8000/api/bills?per_page=15" \
  -H "Accept: application/json"
```

### Using PHP Artisan Tinker

```php
php artisan tinker

// Create a bill
$bill = App\Models\Bill::create([
    'amount' => 150.75,
    'category' => 'Utilities',
    'bill_account' => 'ACCOUNT-001',
    'due_date' => '2024-03-15',
    'is_paid' => false
]);

// Get all bills
App\Models\Bill::orderBy('created_at', 'desc')->paginate(15);
```

## Test Coverage

The test suite covers:
- ✅ Successful bill creation
- ✅ Validation errors
- ✅ Pagination functionality
- ✅ Date ordering (desc)
- ✅ Boolean field handling (is_paid)

## Notes

- Tests use `RefreshDatabase` trait to ensure clean state
- All tests run in isolation
- Database is reset between tests
- Uses SQLite in-memory database for testing (configured in phpunit.xml)
