## Bills API – Frontend Documentation

Base URL examples:
- Production (Render): your deployed URL, e.g. `https://bill-manager-qqk1.onrender.com`

All endpoints return JSON.

---

## 1. GET `/api/bills` – List bills (paginated)

**Method:** `GET`  
**URL:** `/api/bills`

### Request

- **Query params**
  - `per_page` (optional, number) – items per page, default `15`
  - `page` (optional, number) – page number, default `1`

- **Headers**
  - `Accept: application/json`

### Success Response

- **Status:** `200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 73,
      "amount": "372.72",
      "category": "Internet",
      "bill_account": "ACCOUNT-2272",
      "due_date": "2026-02-17",
      "is_paid": false
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7,
    "from": 1,
    "to": 15
  }
}
```

**Notes:**
- `amount` is a **string** with 2 decimal places.
- `due_date` is a **string** in `YYYY-MM-DD` format (no time).
- `created_at` and `updated_at` are **not** included.
- Results are ordered by newest first.

### Error Responses

- **500 Internal Server Error**

```json
{
  "success": false,
  "message": "Server error",
  "error": "..."   // debug message, not for UI
}
```

---

## 2. GET `/api/bills/{id}` – Get single bill

**Method:** `GET`  
**URL:** `/api/bills/{id}`

### Request

- **Path params**
  - `id` (required, integer) – bill ID

- **Headers**
  - `Accept: application/json`

### Success Response

- **Status:** `200 OK`

```json
{
  "success": true,
  "data": {
    "id": 73,
    "amount": "372.72",
    "category": "Internet",
    "bill_account": "ACCOUNT-2272",
    "due_date": "2026-02-17",
    "is_paid": false
  }
}
```

### Not Found

- **Status:** `404 Not Found`

```json
{
  "success": false,
  "message": "Bill not found"
}
```

### Server Error

Same shape as above `500` example.

---

## 3. POST `/api/bills` – Create bill

**Method:** `POST`  
**URL:** `/api/bills`

### Request

- **Headers**
  - `Content-Type: application/json`
  - `Accept: application/json`

- **Body (JSON)**

```json
{
  "amount": 3500,
  "category": "WiFi",
  "bill_account": "Apa Seema",
  "due_date": "2026-02-15",
  "is_paid": false
}
```

**Field details:**

| Field         | Type    | Required | Description                                |
|--------------|---------|----------|--------------------------------------------|
| `amount`     | number  | yes      | Bill amount, `>= 0`                        |
| `category`   | string  | yes      | Category, e.g. `"WiFi"`, `"Electricity"`   |
| `bill_account` | string | yes      | Who/what this bill belongs to              |
| `due_date`   | string  | yes      | `YYYY-MM-DD`, e.g. `"2026-02-15"`         |
| `is_paid`    | boolean | no       | Defaults to `false` if not provided        |

### Success Response

- **Status:** `201 Created`

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
    "is_paid": false
  }
}
```

### Validation Error

- **Status:** `422 Unprocessable Entity`

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

### Server Error

- **Status:** `500 Internal Server Error`

```json
{
  "success": false,
  "message": "Failed to create bill",
  "error": "..."
}
```

---

## 4. PUT/PATCH `/api/bills/{id}` – Update bill

**Methods:** `PUT`, `PATCH`  
**URL:** `/api/bills/{id}`

### Request

- **Path params**
  - `id` (required, integer) – bill ID

- **Headers**
  - `Content-Type: application/json`
  - `Accept: application/json`

- **Body (JSON)** – all fields are **optional** (partial update)

```json
{
  "amount": 4000,
  "category": "Internet",
  "bill_account": "New Account Name",
  "due_date": "2026-03-01",
  "is_paid": true
}
```

Fields use the same rules as the POST body:

| Field         | Type    | Required | Description                         |
|--------------|---------|----------|-------------------------------------|
| `amount`     | number  | no       | Bill amount, `>= 0`                 |
| `category`   | string  | no       | Category                            |
| `bill_account` | string | no       | Who/what this bill belongs to       |
| `due_date`   | string  | no       | `YYYY-MM-DD`                        |
| `is_paid`    | boolean | no       | Payment status                      |

### Success Response

- **Status:** `200 OK`

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
    "is_paid": true
  }
}
```

### Validation Error

- **Status:** `422 Unprocessable Entity`

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "amount": [
      "The amount must be a number."
    ]
  }
}
```

### Not Found

- **Status:** `404 Not Found`

```json
{
  "success": false,
  "message": "Bill not found"
}
```

### Server Error

- **Status:** `500 Internal Server Error`

```json
{
  "success": false,
  "message": "Failed to update bill",
  "error": "..."
}
```

---

## 5. DELETE `/api/bills/{id}` – Soft delete bill

**Method:** `DELETE`  
**URL:** `/api/bills/{id}`

### Request

- **Path params**
  - `id` (required, integer) – bill ID

- **Headers**
  - `Accept: application/json`

### Success Response

- **Status:** `200 OK`

```json
{
  "success": true,
  "message": "Bill deleted successfully"
}
```

> Note: This is a **soft delete** – the record stays in the database with a `deleted_at` timestamp and is excluded from normal queries.

### Not Found

- **Status:** `404 Not Found`

```json
{
  "success": false,
  "message": "Bill not found"
}
```

### Server Error

- **Status:** `500 Internal Server Error`

```json
{
  "success": false,
  "message": "Failed to delete bill",
  "error": "..."
}
```

---

## Status Codes Summary

- `200 OK` – Successful GET / single GET / update / delete
- `201 Created` – Bill created
- `404 Not Found` – Bill not found
- `422 Unprocessable Entity` – Validation error on POST/PUT/PATCH
- `500 Internal Server Error` – Unexpected server error

