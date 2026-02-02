<?php

use App\Http\Controllers\Api\BillController;
use Illuminate\Support\Facades\Route;

Route::get('/bills', [BillController::class, 'index']);
Route::post('/bills', [BillController::class, 'store']);
