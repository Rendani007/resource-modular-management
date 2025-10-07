<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;


/**
 * Authentication Controller
 *
 * Handles secure user authentication with multi-tenant support
 * Implements industry-standard security practices
 */
class AuthController extends Controller
{
   /**
     * User Login
     *
     * Security features:
     * - Rate limiting
     * - Account lockout after failed attempts
     * - IP tracking
     * - Secure token generation
     */

   public function login(Request $request): JsonResponse
   {
    //Rate limmiting: 5 attempts per minute per IP
    $rateLimitKey = 'login' . $request->ip();

    if(RateLimiter::tooManyAttempts($rateLimitKey, 5)){
        $seconds = RateLimiter::availableIn($rateLimitKey);
        return response()->json([
            'error' => 'Too many attempts. Please try again in '. $seconds . 'seconds.'
        ], 429);
    }

    //validate input
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required|string',
        'tenant_slug' => 'required|string|exists:tenants,slug', // Ensure tenant exists
    ]);

    if($validator->fails()){
        RateLimiter::hit($rateLimitKey);
        return response()->json([
            'error' => 'Validation failed',
            'details' => $validator->errors()
        ], 422);
    }

    //get tenant
    $tenant = Tenant::where('slug', $request->tenant_slug)->active()->first();
    if(!$tenant){
        RateLimiter::hit($rateLimitKey);
        return response()->json(['error' => 'Invalid tenant'], 401);
    }

    //find the user within the tenant
    $user = User::where('email', $request->email)
    ->where('tenant_id', $tenant->id)
    ->active()
    ->first();

    // Check if user exists and account is not locked
    if(!$user){
        RateLimiter::hit($rateLimitKey);
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    if($user->isLocked()){
        return response()->json([
            'error' => 'Account is temporarily locked due to too many failed attempts'
        ], 423);
    }

    //Verify password
    if(!Hash::check($request->password, $user->password)){
        RateLimiter::hit($rateLimitKey);
        $user->incrementLoginAttempts();
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    //Successful login
    RateLimiter::clear($rateLimitKey);
    $user->incrementLoginAttempts();

    //generate secure api token with expiration
    $token = $user->createToken(
        name:'API Token',
        expiresAt: now()->addHours(8) //8 hour expiration date
    )->plainTextToken;

    return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'job_title' => $user->job_title,
                'is_tenant_admin' => $user->is_tenant_admin,
                'must_change_password' => $user->must_change_password,
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'industry' => $tenant->industry,
                'enabled_modules' => $tenant->enabled_modules,
                'plan' => $tenant->plan,
                'primary_color' => $tenant->primary_color,
            ],
            'token' => $token,
            'expires_at' => now()->addHours(8)->toISOString(),
        ]);

   }



   /**
     * User Registration (Tenant Admin Only)
     *
     * Only existing tenant admins can register new users
     */
    public function register(Request $request): JsonResponse
    {
        //Validate inputs with strong password requirements
        $validator = Validator::make($request->all(),[
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()],
            'job_title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'employee_id' => 'nullable|string|max:50',
            'is_tenant_admin' => 'boolean',
        ]);

        //if val fails
        if($validator->fails()){
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ],422);
        }

        $currentUser = $request->user();
        $tenant = $currentUser->tenant;

        //check if current user can register new users
        if(!$currentUser->is_tenant_admin){
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        //check if email already exists within tenant
        $existingUser = User::where('email', $request->email)
        ->where('tenant_id', $tenant->id)
        ->first();

        if($existingUser){
            return response()->json(['error' => 'Email already exists'], 409);
        }

        //check tenant user limit
        $userCount = User::where('tenant_id', $tenant->id)->count();
        if($userCount > $tenant->max_users){
            return response()->json([
                'error' => 'Tenant user limit reached',
                'max_users' => $tenant->max_users
            ], 409);
        }

        // Create new user
        $user = User::create([
            'tenant_id' => $tenant->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'job_title' => $request->job_title,
            'department' => $request->department,
            'employee_id' => $request->employee_id,
            'is_tenant_admin' => $request->is_tenant_admin ?? false,
            'must_change_password' => true, // Force password change on first login
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'job_title' => $user->job_title,
                'is_tenant_admin' => $user->is_tenant_admin,
            ]
        ], 201);

    }

    /**
     * User Logout
     *
     * Revokes the current access token
     */
    public function logout(Request $request): JsonResponse{
        //revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get Current User Profile
     */
    public function profile(Request $request): JsonResponse{
        $user = $request->user()->load('tenant');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'job_title' => $user->job_title,
                'department' => $user->department,
                'employee_id' => $user->employee_id,
                'is_tenant_admin' => $user->is_tenant_admin,
                'must_change_password' => $user->must_change_password,
                'last_login_at' => $user->last_login_at,
                'avatar_path' => $user->avatar_path,
            ],
            'tenant' => [
                'id' => $user->tenant->id,
                'name' => $user->tenant->name,
                'slug' => $user->tenant->slug,
                'industry' => $user->tenant->industry,
                'enabled_modules' => $user->tenant->enabled_modules,
                'plan' => $user->tenant->plan,
                'primary_color' => $user->tenant->primary_color,
            ]
        ]);
    }

     /**
     * Change Password
     */
    public function changePassword(Request $request):JsonResponse{
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()],
        ]);

        if($validator->fails()){
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        //verify current password
        if(!Hash::check($request->current_password, $user->password)){
            return response()->json([
                'error' => 'Current password is incorrect'
            ], 401);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
            'must_change_password' => false,
        ]);

        //revoke all other tokens for security
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();
        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }
}
