<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the home route for your application.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        // Define the 'api' rate limiter for API routes
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        // Tight login bucket
        RateLimiter::for('logins', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->ip().'|'.
                strtolower($request->input('email', '')) .'|'.
                strtolower($request->input('tenant_slug', ''))
            );
        });

        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->namespace ?? null)
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace ?? null)
            ->group(base_path('routes/api.php'));
    }
}
