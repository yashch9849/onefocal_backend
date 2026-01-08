<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$roles)
    {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
    
        // Load role relationship if not already loaded
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }
    
        if (!$user->role || !in_array($user->role->name, $roles)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
    
        return $next($request);
    }
    
}
