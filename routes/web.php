<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackingController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/t/{trackingId}/open', [TrackingController::class, 'open']);
Route::get('/t/{trackingId}/click', [TrackingController::class, 'click']);
