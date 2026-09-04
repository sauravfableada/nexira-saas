<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ProductCategoryController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Custom POST route for update to bypass PHP's PUT/multipart limitation
    Route::post('product-categories/{product_category}', [ProductCategoryController::class, 'update']);
    Route::apiResource('product-categories', ProductCategoryController::class);
});
