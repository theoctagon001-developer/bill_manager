<?php

use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\SavingsController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;



Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/savings', [SavingsController::class, 'index']);

Route::prefix('bills')->controller(BillController::class)->group(function () {
    Route::get('/dashboard', 'billsDashboard');
    Route::post('/clean', 'cleanImages');
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'show');
    Route::match(['put', 'patch'], '/{id}', 'update');
    Route::delete('/{id}', 'destroy');
    Route::post('/{id}/image', 'updateImage');
    Route::delete('/{id}/image', 'deleteImage');
    Route::get('/{id}/image/download', 'downloadImage');
});

Route::prefix('budgets')->controller(BudgetController::class)->group(function () {
    Route::get('/search', 'search');
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'show');
    Route::match(['put', 'patch'], '/{id}', 'update');
    Route::delete('/{id}', 'destroy');
});

Route::prefix('expenses')->controller(ExpenseController::class)->group(function () {
    Route::get('/search', 'search');
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'show');
    Route::match(['put', 'patch'], '/{id}', 'update');
    Route::delete('/{id}', 'destroy');
});


