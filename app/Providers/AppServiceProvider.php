<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

         Response::macro('ok', function ($data = [], $meta = []) {
            return response()->json([
                'status' => 'ok',
                'data'   => $data,
                'meta'   => $meta,
            ]);
        });

        Response::macro('fail', function (string $message, int $code = 400, array $errors = []) {
            return response()->json([
                'status'  => 'error',
                'message' => $message,
                'errors'  => $errors,
            ], $code);
        });

    }
}
