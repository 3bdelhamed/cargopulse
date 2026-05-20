<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/track/{tracking_number}', [\App\Http\Controllers\Web\PublicTrackingController::class, 'show']);
