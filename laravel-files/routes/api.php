<?php

use App\Http\Controllers\AgroController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::middleware('throttle:90,1')->group(function () {
    Route::get('/cultures', [AgroController::class, 'cultures']);
    Route::get('/offers', [AgroController::class, 'offers']);
    Route::get('/zones', [AgroController::class, 'zones']);
});

Route::middleware('apiauth')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/diagnostics', [AgroController::class, 'diagnose']);
    Route::get('/diagnostics/mine', [AgroController::class, 'mine']);
    Route::get('/diagnostics/map', [AgroController::class, 'map']);
    Route::post('/offers', [AgroController::class, 'storeOffer']);
    Route::post('/offers/{id}/buy', [AgroController::class, 'buy'])->whereNumber('id');
    Route::post('/zones/{id}/harvest', [AgroController::class, 'harvest'])->whereNumber('id');
});

Route::middleware('apiauth:admin')->get('/dashboard', [AgroController::class, 'dashboard']);
