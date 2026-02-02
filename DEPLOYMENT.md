# Laravel Bills API - Deployment Guide

## Project Overview
This is a Laravel 11 API project for managing bills with the following features:
- POST endpoint to create bills
- GET endpoint to retrieve bills with pagination (ordered by date desc)

## Database Setup

### Environment Variables
Make sure to set the following in your `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=your_host
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## API Endpoints

### GET /api/bills
Retrieve all bills with pagination (ordered by created_at desc)

**Query Parameters:**
- `per_page` (optional): Number of items per page (default: 15)

**Response:**
```json
{
    "success": true,
    "data": [...],
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

### POST /api/bills
Create a new bill

**Request Body:**
```json
{
    "amount": 100.50,
    "category": "Utilities",
    "bill_account": "Account-123",
    "due_date": "2024-02-15",
    "is_paid": false
}
```

**Response:**
```json
{
    "success": true,
    "message": "Bill created successfully",
    "data": {...}
}
```

## Database Migration

Run migrations to create the bills table:
```bash
php artisan migrate
```

## Docker Deployment on Render

1. Push your code to a Git repository
2. Connect your repository to Render
3. Render will automatically detect the `Dockerfile` and `render.yaml`
4. Set your environment variables in Render dashboard
5. Deploy!

The Dockerfile is configured to:
- Use PHP 8.2 with Apache
- Install all dependencies
- Set proper permissions
- Run migrations on startup
- Serve the application

## Local Development

1. Install dependencies:
```bash
composer install
```

2. Copy `.env.example` to `.env` and configure your database

3. Generate application key:
```bash
php artisan key:generate
```

4. Run migrations:
```bash
php artisan migrate
```

5. Start the development server:
```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api/bills`
