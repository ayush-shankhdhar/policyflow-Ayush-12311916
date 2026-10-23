<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\MockInsurerCallbackController;
use App\Http\Middleware\VerifyCsrfToken;




Route::get('/', function () {
    return view('welcome');
});
