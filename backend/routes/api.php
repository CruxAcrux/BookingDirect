<?php

use App\Http\Controllers\Api\V1\Admin\ShopController as AdminShopController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', LoginController::class)->middleware('throttle:30,1');
    Route::post('forgot-password', ForgotPasswordController::class)->middleware('throttle:6,1');
    Route::post('reset-password', ResetPasswordController::class)->middleware('throttle:6,1');
    Route::post('logout', LogoutController::class)->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', MeController::class);

    Route::prefix('admin')->middleware('can:administer-platform')->group(function () {
        Route::get('shops', [AdminShopController::class, 'index']);
        Route::post('shops', [AdminShopController::class, 'store']);
        Route::post('shops/{shop}/deactivate', [AdminShopController::class, 'deactivate']);
        Route::post('shops/{shop}/activate', [AdminShopController::class, 'activate']);
    });
});
