<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\SubscriberController;

Route::get('/campaigns', [CampaignController::class, 'index']);
Route::post('/campaigns', [CampaignController::class, 'store']);
Route::get('/campaigns/{id}', [CampaignController::class, 'show']);
Route::post('/campaigns/{id}/dispatch', [CampaignController::class, 'dispatch']);
Route::get('/campaigns/{id}/stats', [CampaignController::class, 'stats']);

Route::apiResource('templates', TemplateController::class);
Route::apiResource('subscribers', SubscriberController::class);
Route::post('/subscribers/import', [SubscriberController::class, 'import']);
