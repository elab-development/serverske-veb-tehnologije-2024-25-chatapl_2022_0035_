<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CsrfProtectionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip CSRF check for GET requests and API routes
        if ($request->isMethod('GET') || $request->is('api/*')) {
            return $next($request);
        }
        
        // Check if request has valid CSRF token
        if (!$this->validateCsrfToken($request)) {
            return response()->json([
                'success' => false,
                'message' => 'CSRF token mismatch. Please refresh the page and try again.',
                'error_code' => 'CSRF_TOKEN_MISMATCH'
            ], 419);
        }
        
        // Regenerate CSRF token for security
        $this->regenerateCsrfToken();
        
        return $next($request);
    }
    
    /**
     * Validate CSRF token.
     */
    protected function validateCsrfToken(Request $request): bool
    {
        $token = $this->getTokenFromRequest($request);
        $sessionToken = Session::token();
        
        if (!$token || !$sessionToken) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }
    
    /**
     * Get CSRF token from request.
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        // Check for token in header
        $token = $request->header('X-CSRF-TOKEN');
        
        if (!$token) {
            // Check for token in request body
            $token = $request->input('_token');
        }
        
        if (!$token) {
            // Check for token in query string (for legacy support)
            $token = $request->query('_token');
        }
        
        return $token;
    }
    
    /**
     * Regenerate CSRF token.
     */
    protected function regenerateCsrfToken(): void
    {
        if (Session::isStarted()) {
            Session::regenerateToken();
        }
    }
    
    /**
     * Generate a new CSRF token.
     */
    public static function generateToken(): string
    {
        $token = Str::random(40);
        Session::put('_token', $token);
        return $token;
    }
    
    /**
     * Verify CSRF token without regenerating.
     */
    public static function verifyToken(string $token): bool
    {
        $sessionToken = Session::token();
        return hash_equals($sessionToken, $token);
    }
    
    /**
     * Get current CSRF token.
     */
    public static function getToken(): string
    {
        return Session::token();
    }
    
    /**
     * Check if request is exempt from CSRF protection.
     */
    protected function isExempt(Request $request): bool
    {
        $exemptRoutes = [
            'api/webhook/*',
            'api/payment/*',
            'api/external/*',
        ];
        
        foreach ($exemptRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log CSRF token mismatch attempts.
     */
    protected function logCsrfMismatch(Request $request): void
    {
        \Log::warning('CSRF token mismatch detected', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'provided_token' => $this->getTokenFromRequest($request),
            'session_token' => Session::token(),
            'user_id' => $request->user()?->id,
        ]);
    }
} 