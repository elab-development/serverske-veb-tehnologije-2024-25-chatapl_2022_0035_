<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class XssProtectionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize request data
        $this->sanitizeRequest($request);
        
        $response = $next($request);
        
        // Add XSS protection headers
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        return $response;
    }
    
    /**
     * Sanitize request data to prevent XSS attacks.
     */
    protected function sanitizeRequest(Request $request): void
    {
        $input = $request->all();
        $sanitized = $this->sanitizeArray($input);
        
        // Replace request data with sanitized data
        $request->replace($sanitized);
    }
    
    /**
     * Recursively sanitize array data.
     */
    protected function sanitizeArray(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } else {
                $sanitized[$key] = $this->sanitizeValue($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize a single value.
     */
    protected function sanitizeValue($value): string
    {
        if (!is_string($value)) {
            return $value;
        }
        
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        
        // Remove common XSS vectors
        $value = $this->removeXssVectors($value);
        
        // HTML encode special characters
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove any remaining script tags
        $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $value);
        
        return $value;
    }
    
    /**
     * Remove common XSS attack vectors.
     */
    protected function removeXssVectors(string $value): string
    {
        $patterns = [
            // JavaScript events
            '/on\w+\s*=/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/data:/i',
            
            // Script tags
            '/<script/i',
            '/<\/script>/i',
            
            // Iframe tags
            '/<iframe/i',
            '/<\/iframe>/i',
            
            // Object tags
            '/<object/i',
            '/<\/object>/i',
            
            // Embed tags
            '/<embed/i',
            '/<\/embed>/i',
            
            // Applet tags
            '/<applet/i',
            '/<\/applet>/i',
            
            // Form tags (in some contexts)
            '/<form/i',
            '/<\/form>/i',
            
            // Input tags with dangerous attributes
            '/<input[^>]*\s+(on\w+|javascript:|vbscript:)/i',
            
            // Dangerous CSS expressions
            '/expression\s*\(/i',
            '/url\s*\(\s*["\']?\s*javascript:/i',
            
            // Unicode escape sequences
            '/\\u[0-9a-fA-F]{4}/',
            '/\\x[0-9a-fA-F]{2}/',
        ];
        
        $replacements = [
            'on_event_disabled=',
            'javascript_disabled:',
            'vbscript_disabled:',
            'data_disabled:',
            '&lt;script',
            '&lt;/script&gt;',
            '&lt;iframe',
            '&lt;/iframe&gt;',
            '&lt;object',
            '&lt;/object&gt;',
            '&lt;embed',
            '&lt;/embed&gt;',
            '&lt;applet',
            '&lt;/applet&gt;',
            '&lt;form',
            '&lt;/form&gt;',
            '&lt;input disabled',
            'expression_disabled(',
            'url_disabled:',
            'u_disabled',
            'x_disabled',
        ];
        
        return preg_replace($patterns, $replacements, $value);
    }
    
    /**
     * Check if a string contains potentially dangerous content.
     */
    public static function isDangerous(string $value): bool
    {
        $dangerousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i',
            '/expression\s*\(/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log suspicious XSS attempts.
     */
    protected function logSuspiciousActivity(Request $request, string $originalValue): void
    {
        if (self::isDangerous($originalValue)) {
            \Log::warning('Potential XSS attempt detected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'suspicious_value' => $originalValue,
                'user_id' => $request->user()?->id,
            ]);
        }
    }
} 