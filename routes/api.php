<?php

use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\SavingsController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/bills', [BillController::class, 'index']);
Route::post('/bills', [BillController::class, 'store']);
Route::get('/bills/dashboard', [BillController::class, 'billsDashboard']);
Route::get('/bills/{id}', [BillController::class, 'show']);
Route::match(['put', 'patch'], '/bills/{id}', [BillController::class, 'update']);
Route::delete('/bills/{id}', [BillController::class, 'destroy']);

Route::post('/bills/{id}/image', [BillController::class, 'updateImage']);
Route::delete('/bills/{id}/image', [BillController::class, 'deleteImage']);
Route::get('/bills/{id}/image/download', [BillController::class, 'downloadImage']);

Route::get('/budgets', [BudgetController::class, 'index']);
Route::post('/budgets', [BudgetController::class, 'store']);
Route::get('/budgets/search', [BudgetController::class, 'search']);
Route::get('/budgets/{id}', [BudgetController::class, 'show']);
Route::match(['put', 'patch'], '/budgets/{id}', [BudgetController::class, 'update']);
Route::delete('/budgets/{id}', [BudgetController::class, 'destroy']);

Route::get('/expenses', [ExpenseController::class, 'index']);
Route::post('/expenses', [ExpenseController::class, 'store']);
Route::get('/expenses/search', [ExpenseController::class, 'search']);
Route::get('/expenses/{id}', [ExpenseController::class, 'show']);
Route::match(['put', 'patch'], '/expenses/{id}', [ExpenseController::class, 'update']);
Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy']);

Route::get('/savings', [SavingsController::class, 'index']);
Route::get('/dashboard', [DashboardController::class, 'index']);
