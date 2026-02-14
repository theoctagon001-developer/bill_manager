<?php

use App\Http\Controllers\Api\BillController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BillController::class, 'dashboard']);
Route::get('/bills', [BillController::class, 'index']);
Route::post('/bills', [BillController::class, 'store']);
Route::get('/bills/{id}', [BillController::class, 'show']);
Route::match(['put', 'patch'], '/bills/{id}', [BillController::class, 'update']);
Route::delete('/bills/{id}', [BillController::class, 'destroy']);


Route::post('/bills/{id}/image', [BillController::class, 'updateImage']);
Route::delete('/bills/{id}/image', [BillController::class, 'deleteImage']);
Route::get('/bills/{id}/image/download', [BillController::class, 'downloadImage']);
