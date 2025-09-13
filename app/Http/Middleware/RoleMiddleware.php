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
    public function handle(Request $request, Closure $next, ...$roles): Response
    {

        //check if user is logged in
        if (!auth()->check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unfortunately, You are not authorized'
            ], 401);
        }

        //validate role access
        if (!in_array(auth()->user()->role, $roles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        return $next($request);
    }
} 
