<?php

use App\Http\Controllers\Api\V1\Admin\ShopController as AdminShopController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Barbers\BarberAvatarController;
use App\Http\Controllers\Api\V1\Barbers\BarberController;
use App\Http\Controllers\Api\V1\Barbers\BarberScheduleController;
use App\Http\Controllers\Api\V1\Barbers\PortfolioController;
use App\Http\Controllers\Api\V1\Barbers\TimeOffController;
use App\Http\Controllers\Api\V1\Bookings\BookingController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\Public\AvailabilityController;
use App\Http\Controllers\Api\V1\Public\CustomerBookingController;
use App\Http\Controllers\Api\V1\Public\CustomerSessionController;
use App\Http\Controllers\Api\V1\Public\ManageBookingController;
use App\Http\Controllers\Api\V1\Public\PublicShopController;
use App\Http\Controllers\Api\V1\Services\ServiceController;
use App\Http\Controllers\Api\V1\Shop\OpeningHoursController;
use App\Http\Controllers\Api\V1\Shop\ShopController;
use App\Http\Controllers\Api\V1\Shop\ShopImageController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', LoginController::class)->middleware('throttle:30,1');
    Route::post('forgot-password', ForgotPasswordController::class)->middleware('throttle:6,1');
    Route::post('reset-password', ResetPasswordController::class)->middleware('throttle:6,1');
    Route::post('logout', LogoutController::class)->middleware('auth:sanctum');
});

Route::prefix('public')->group(function () {
    Route::prefix('shops/{shop:slug}')->middleware('public.shop')->group(function () {
        Route::get('/', [PublicShopController::class, 'show']);

        Route::middleware('throttle:availability')->group(function () {
            Route::get('availability', [AvailabilityController::class, 'index']);
            Route::get('availability/next', [AvailabilityController::class, 'next']);
        });

        Route::post('otp', [CustomerSessionController::class, 'requestCode'])->middleware('throttle:otp');
        Route::post('otp/verify', [CustomerSessionController::class, 'verify'])->middleware('throttle:public-booking');
        Route::get('me', [CustomerSessionController::class, 'me']);
        Route::post('logout', [CustomerSessionController::class, 'logout']);

        Route::get('me/bookings', [CustomerBookingController::class, 'index']);
        Route::middleware('throttle:public-booking')->group(function () {
            Route::post('bookings', [CustomerBookingController::class, 'store']);
            Route::post('me/bookings/{booking}/cancel', [CustomerBookingController::class, 'cancel'])->whereNumber('booking');
            Route::post('me/bookings/{booking}/reschedule', [CustomerBookingController::class, 'reschedule'])->whereNumber('booking');
        });
    });

    Route::prefix('bookings/{token}')->middleware(['manage.booking', 'throttle:public-booking'])->group(function () {
        Route::get('/', [ManageBookingController::class, 'show']);
        Route::post('cancel', [ManageBookingController::class, 'cancel']);
        Route::post('reschedule', [ManageBookingController::class, 'reschedule']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', MeController::class);

    Route::prefix('admin')->middleware('can:administer-platform')->group(function () {
        Route::get('shops', [AdminShopController::class, 'index']);
        Route::post('shops', [AdminShopController::class, 'store']);
        Route::post('shops/{shop}/deactivate', [AdminShopController::class, 'deactivate']);
        Route::post('shops/{shop}/activate', [AdminShopController::class, 'activate']);
    });

    Route::middleware('shop')->group(function () {
        Route::get('shop', [ShopController::class, 'show']);
        Route::patch('shop', [ShopController::class, 'update']);
        Route::post('shop/{type}', [ShopImageController::class, 'store'])->whereIn('type', ['logo', 'cover']);
        Route::delete('shop/{type}', [ShopImageController::class, 'destroy'])->whereIn('type', ['logo', 'cover']);
        Route::get('shop/opening-hours', [OpeningHoursController::class, 'index']);
        Route::put('shop/opening-hours', [OpeningHoursController::class, 'update']);

        Route::apiResource('bookings', BookingController::class)->except('destroy');
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);
        Route::post('bookings/{booking}/outcome', [BookingController::class, 'outcome']);

        Route::put('services/order', [ServiceController::class, 'reorder']);
        Route::apiResource('services', ServiceController::class)->except('show');

        Route::put('barbers/order', [BarberController::class, 'reorder']);
        Route::apiResource('barbers', BarberController::class);

        Route::prefix('barbers/{barber}')->scopeBindings()->group(function () {
            Route::post('invite', [BarberController::class, 'invite']);
            Route::put('services', [BarberController::class, 'syncServices']);
            Route::post('avatar', [BarberAvatarController::class, 'store']);
            Route::delete('avatar', [BarberAvatarController::class, 'destroy']);

            Route::get('working-hours', [BarberScheduleController::class, 'workingHours']);
            Route::put('working-hours', [BarberScheduleController::class, 'updateWorkingHours']);
            Route::get('breaks', [BarberScheduleController::class, 'breaks']);
            Route::put('breaks', [BarberScheduleController::class, 'updateBreaks']);

            Route::apiResource('time-off', TimeOffController::class)
                ->except('show')
                ->parameters(['time-off' => 'timeOff']);

            Route::put('portfolio/order', [PortfolioController::class, 'reorder']);
            Route::apiResource('portfolio', PortfolioController::class)
                ->except('show')
                ->parameters(['portfolio' => 'portfolioImage']);
        });
    });
});
