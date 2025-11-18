<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // If no specific roles required, allow access
        if (empty($roles)) {
            return $next($request);
        }

        // Parse roles (handle comma-separated values)
        $allowedRoles = [];
        foreach ($roles as $roleString) {
            if (strpos($roleString, ',') !== false) {
                $allowedRoles = array_merge($allowedRoles, explode(',', $roleString));
            } else {
                $allowedRoles[] = $roleString;
            }
        }

        // Check if user has required role
        if (!in_array($user->role, $allowedRoles)) {
            return response()->view('errors.403', [], 403);
        }

        return $next($request);
    }
}
