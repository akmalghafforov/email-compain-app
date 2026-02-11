<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\SubscriberController;

Route::get('/campaigns', [CampaignController::class, 'index']);
Route::post('/campaigns', [CampaignController::class, 'store']);
Route::get('/campaigns/{id}', [CampaignController::class, 'show']);
Route::put('/campaigns/{id}', [CampaignController::class, 'update']);
Route::post('/campaigns/{id}/dispatch', [CampaignController::class, 'dispatch']);
Route::get('/campaigns/{id}/stats', [CampaignController::class, 'stats']);

Route::get('/templates', [TemplateController::class, 'index']);
Route::post('/templates', [TemplateController::class, 'store']);
Route::get('/templates/{id}', [TemplateController::class, 'show']);
Route::put('/templates/{id}', [TemplateController::class, 'update']);
Route::delete('/templates/{id}', [TemplateController::class, 'destroy']);

Route::get('/subscribers', [SubscriberController::class, 'index']);
Route::post('/subscribers', [SubscriberController::class, 'store']);
Route::get('/subscribers/{id}', [SubscriberController::class, 'show']);
Route::put('/subscribers/{id}', [SubscriberController::class, 'update']);
Route::delete('/subscribers/{id}', [SubscriberController::class, 'destroy']);
Route::post('/subscribers/import', [SubscriberController::class, 'import']);
