<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\MockInsurerCallbackController;
use App\Http\Middleware\VerifyCsrfToken;

Route::post('/mock-insurer/callback', [MockInsurerCallbackController::class, 'handle'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->middleware('api');

Route::get('/', function () {
    return view('welcome');
});
