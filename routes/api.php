<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\AuditLogController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    Route::get('/profile', [AuthController::class, 'getProfile']);
    Route::post('/profile', [AuthController::class, 'updateProfile']);

    // Custom POST route for update to bypass PHP's PUT/multipart limitation
    Route::post('product-categories/{product_category}', [ProductCategoryController::class, 'update']);
    Route::apiResource('product-categories', ProductCategoryController::class);
    // Settings API
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'store']);
    
    // SMTP Specific API
    Route::get('/settings/smtp', [SettingController::class, 'getSmtp']);
    Route::post('/settings/smtp', [SettingController::class, 'storeSmtp']);

    // General Settings API
    Route::get('/settings/general', [SettingController::class, 'getGeneral']);
    Route::post('/settings/general', [SettingController::class, 'storeGeneral']);

    // Audit Logs API
    Route::get('/audit-logs', [AuditLogController::class, 'index']);

    Route::post('plans/{plan}', [App\Http\Controllers\PlanController::class, 'update']);
    Route::apiResource('plans', App\Http\Controllers\PlanController::class);
});
