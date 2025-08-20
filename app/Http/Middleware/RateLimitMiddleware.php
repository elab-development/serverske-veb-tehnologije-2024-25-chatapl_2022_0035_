<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'default'): Response
    {
        $key = $this->resolveRequestSignature($request, $type);
        
        // Different rate limits for different types of requests
        $limits = $this->getRateLimits($type);
        
        if (RateLimiter::tooManyAttempts($key, $limits['max_attempts'])) {
            $retryAfter = RateLimiter::availableIn($key);
            
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
                'error_code' => 'RATE_LIMIT_EXCEEDED'
            ], 429)->withHeaders([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $limits['max_attempts'],
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => time() + $retryAfter,
            ]);
        }
        
        RateLimiter::hit($key, $limits['decay_minutes'] * 60);
        
        $response = $next($request);
        
        // Add rate limit headers to response
        $response->headers->add([
            'X-RateLimit-Limit' => $limits['max_attempts'],
            'X-RateLimit-Remaining' => RateLimiter::remaining($key, $limits['max_attempts']),
            'X-RateLimit-Reset' => time() + ($limits['decay_minutes'] * 60),
        ]);
        
        return $response;
    }
    
    /**
     * Resolve the request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request, string $type): string
    {
        $identifier = match($type) {
            'auth' => 'auth_' . $request->ip(),
            'api' => 'api_' . $request->ip(),
            'login' => 'login_' . $request->ip(),
            'register' => 'register_' . $request->ip(),
            'messages' => 'messages_' . $request->user()?->id ?? $request->ip(),
            'rooms' => 'rooms_' . $request->user()?->id ?? $request->ip(),
            default => 'default_' . $request->ip(),
        };
        
        return sha1($identifier);
    }
    
    /**
     * Get rate limits for different request types.
     */
    protected function getRateLimits(string $type): array
    {
        return match($type) {
            'auth' => ['max_attempts' => 5, 'decay_minutes' => 1],      // 5 attempts per minute
            'login' => ['max_attempts' => 3, 'decay_minutes' => 5],     // 3 attempts per 5 minutes
            'register' => ['max_attempts' => 2, 'decay_minutes' => 10], // 2 attempts per 10 minutes
            'api' => ['max_attempts' => 60, 'decay_minutes' => 1],      // 60 requests per minute
            'messages' => ['max_attempts' => 30, 'decay_minutes' => 1], // 30 messages per minute
            'rooms' => ['max_attempts' => 10, 'decay_minutes' => 1],    // 10 room operations per minute
            default => ['max_attempts' => 100, 'decay_minutes' => 1],   // 100 requests per minute
        };
    }
} 