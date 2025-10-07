<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // force new users to change password before accessing the rest of the API
        $u = $request->user();
        if ($u && $u->must_change_password && !$request->is('api/v1/auth/change-password')) {
            return response()->json([
                'error' => 'Password change required',
                'action' => '/api/v1/auth/change-password'
            ], 428); // 428 Precondition Required
        }

        return $next($request);
    }
}
