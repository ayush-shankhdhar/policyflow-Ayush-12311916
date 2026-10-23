<?php

use App\Http\Controllers\API\PolicyRequestController;
use App\Http\Controllers\API\MockInsurerCallbackController;
use Illuminate\Support\Facades\Route;

Route::post('/policy-requests', [PolicyRequestController::class, 'store']);
Route::get('/policy-requests/{id}', [PolicyRequestController::class, 'show']);
Route::post('/policy-requests/{id}/submit', [PolicyRequestController::class, 'submit']);
Route::get('/policy-requests', [PolicyRequestController::class, 'index']);
Route::post('/mock-insurer/callback', [MockInsurerCallbackController::class, 'handle']);
?>
