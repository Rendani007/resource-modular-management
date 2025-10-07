<?php

use App\Http\Controllers\API\InventoryController;
use App\Http\Controllers\API\StockController;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */

    // Health
    Route::get('/health', function () {
        return response()->json([
            'status'      => 'healthy',
            'version'     => '1.0.0',
            'timestamp'   => now()->toISOString(),
            'environment' => app()->environment(),
            'database'    => 'connected',
        ]);
    });

    // Auth (PUBLIC)
    Route::prefix('auth')->group(function () {
        // Keep login public, add a login-specific rate limit bucket
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:logins');
    });

    // If you implemented feature flags, include ->middleware('module:inventory')
    Route::prefix('inventory')->group(function () {
        // Items
        Route::get('/items', [InventoryController::class, 'index']);
        Route::post('/items', [InventoryController::class, 'store'])->middleware('tenant.admin');
        Route::get('/items/{item}', [InventoryController::class, 'show']);
        Route::put('/items/{item}', [InventoryController::class, 'update'])->middleware('tenant.admin');
        Route::delete('/items/{item}', [InventoryController::class, 'destroy'])->middleware('tenant.admin');

        // Stock movements
        Route::post('/stock/in', [StockController::class, 'stockIn'])->middleware('tenant.admin');
        Route::post('/stock/out', [StockController::class, 'stockOut'])->middleware('tenant.admin');
        Route::post('/stock/transfer', [StockController::class, 'transfer'])->middleware('tenant.admin');

        // Optional: stock by item
        Route::get('/items/{item}/stock', [StockController::class, 'stockForItem']);
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (auth:sanctum)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'throttle:100,1'])->group(function () {

        // Auth (PROTECTED)
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/profile', [AuthController::class, 'profile']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
            Route::post('/register', [AuthController::class, 'register'])
                ->middleware('tenant.admin');

        });

        // Basic user route
        Route::get('/user', function (Request $request) {
            return response()->json([
                'user' => $request->user()->load('tenant'),
            ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Dev Routes (local only)
    |--------------------------------------------------------------------------
    */
    if (app()->environment('local')) {
        Route::prefix('dev')->group(function () {

            Route::get('/create-demo-tenant', function () {
                try {
                    $existingTenant = Tenant::where('slug', 'demo-mining')->first();
                    if ($existingTenant) {
                        return response()->json([
                            'message' => 'Demo tenant already exists',
                            'tenant'  => $existingTenant,
                            'login_credentials' => [
                                'email'       => 'admin@demo-mining.com',
                                'password'    => 'Password123!',
                                'tenant_slug' => 'demo-mining',
                            ],
                        ]);
                    }

                    $tenant = Tenant::create([
                        'name'            => 'Demo Mining Corp',
                        'slug'            => 'demo-mining',
                        'industry'        => 'mining',
                        'plan'            => 'professional',
                        'enabled_modules' => ['inventory', 'fleet', 'maintenance', 'safety'],
                        'max_users'       => 50,
                        'admin_email'     => 'admin@demo-mining.com',
                        'is_active'       => true,
                        'primary_color'   => '#0ea5e9',
                        'trial_ends_at'   => now()->addDays(14),
                    ]);

                    $user = User::create([
                        'tenant_id'       => $tenant->id,
                        'first_name'      => 'Admin',
                        'last_name'       => 'User',
                        'email'           => 'admin@demo-mining.com',
                        'password'        => bcrypt('Password123!'),
                        'job_title'       => 'System Administrator',
                        'is_tenant_admin' => true,
                        'is_active'       => true,
                    ]);

                    $regularUser = User::create([
                        'tenant_id'       => $tenant->id,
                        'first_name'      => 'John',
                        'last_name'       => 'Operator',
                        'email'           => 'operator@demo-mining.com',
                        'password'        => bcrypt('Password123!'),
                        'job_title'       => 'Equipment Operator',
                        'department'      => 'Operations',
                        'is_tenant_admin' => false,
                        'is_active'       => true,
                    ]);

                    return response()->json([
                        'message'        => 'Demo tenant created successfully',
                        'tenant'         => $tenant,
                        'users_created'  => 2,
                        'login_credentials' => [
                            'admin' => [
                                'email'       => 'admin@demo-mining.com',
                                'password'    => 'Password123!',
                                'tenant_slug' => 'demo-mining',
                                'role'        => 'tenant_admin',
                            ],
                            'regular_user' => [
                                'email'       => 'operator@demo-mining.com',
                                'password'    => 'Password123!',
                                'tenant_slug' => 'demo-mining',
                                'role'        => 'regular_user',
                            ],
                        ],
                        'next_steps' => [
                            '1. Test login: POST /api/v1/auth/login',
                            '2. Get profile: GET /api/v1/auth/profile (with token)',
                            '3. Create more users: POST /api/v1/auth/register (admin only)',
                        ],
                    ], 201);

                } catch (\Exception $e) {
                    return response()->json([
                        'error'   => 'Failed to create demo tenant',
                        'message' => $e->getMessage(),
                    ], 500);
                }
            });

            Route::get('/reset-demo', function () {
                User::where('email', 'like', '%demo-mining.com')->delete();
                Tenant::where('slug', 'demo-mining')->delete();
                return response()->json(['message' => 'Demo data reset']);
            });
        });
    }

    // Optional nice 404 for API
    Route::fallback(fn () => response()->json(['status' => 'error', 'message' => 'Route not found'], 404));
});
