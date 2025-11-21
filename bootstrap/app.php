<?php

use App\Http\Middleware\EnsureTenantAccess;
use App\Http\Middleware\EnsureTenantAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Middleware\HandleCors;


return Application::configure(basePath: dirname(__DIR__))
   ->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    apiPrefix: 'api',
)
->withMiddleware(function (Middleware $middleware) {
        // run CORS for every request
        $middleware->append(HandleCors::class);
    $middleware->alias([
        'tenant'        => EnsureTenantAccess::class,
        'tenant.admin'  => EnsureTenantAdmin::class,
        // 'module'     => \App\Http\Middleware\RequireModule::class, // if you add feature flags
    ]);

    // API group for token auth:
    $middleware->group('api', [
        
        'throttle:api',
        SubstituteBindings::class,
        EnsureTenantAccess::class,
        ]);
    })
    ->withExceptions(function ($exceptions) {
        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $e) {
            return response()->json(['status'=>'error','message'=>'Not found'], 404);
        });
        $exceptions->render(function (ValidationException $e) {
            return response()->json([
                'status'=>'error',
                'message'=>'Validation failed',
                'errors'=>$e->errors(),
            ], 422);
        });
    })->create();
