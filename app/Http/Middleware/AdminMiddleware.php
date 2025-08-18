<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You must be logged in to access this resource'
            ], 401);
        }

        $user = Auth::user();
        
        // Proveri da li korisnik ima admin ulogu
        if (!$user->is_admin) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You do not have permission to access this resource'
            ], 403);
        }

        return $next($request);
    }
} 