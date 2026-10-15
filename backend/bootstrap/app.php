<?php

use App\Http\Middleware\ResolveManagedBooking;
use App\Http\Middleware\ResolvePublicShop;
use App\Http\Middleware\ResolveStaffShop;
use App\Http\Middleware\SetLocaleFromRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->api(append: [SetLocaleFromRequest::class]);
        $middleware->alias([
            'shop' => ResolveStaffShop::class,
            'public.shop' => ResolvePublicShop::class,
            'manage.booking' => ResolveManagedBooking::class,
        ]);
        // The current shop must be known before route model binding runs, so bound
        // models are already scoped to it and other shops' records resolve to 404.
        $middleware->prependToPriorityList(SubstituteBindings::class, ResolveStaffShop::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
