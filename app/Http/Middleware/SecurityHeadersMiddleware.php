<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add comprehensive security headers
        $this->addSecurityHeaders($response);

        return $response;
    }

    /**
     * Add security headers to response.
     */
    protected function addSecurityHeaders(Response $response): void
    {
        // XSS Protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Content Type Options
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Frame Options
        $response->headers->set('X-Frame-Options', 'DENY');
        
        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Content Security Policy
        $csp = $this->buildContentSecurityPolicy();
        $response->headers->set('Content-Security-Policy', $csp);
        
        // Permissions Policy
        $permissionsPolicy = $this->buildPermissionsPolicy();
        $response->headers->set('Permissions-Policy', $permissionsPolicy);
        
        // Strict Transport Security (HSTS)
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        
        // Cache Control for sensitive data
        if ($this->isSensitiveRoute()) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }
        
        // Feature Policy (legacy)
        $featurePolicy = $this->buildFeaturePolicy();
        $response->headers->set('Feature-Policy', $featurePolicy);
        
        // Clear Site Data (for logout)
        if ($this->isLogoutRoute()) {
            $response->headers->set('Clear-Site-Data', '"cache", "cookies", "storage"');
        }
    }

    /**
     * Build Content Security Policy.
     */
    protected function buildContentSecurityPolicy(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.pusher.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https: blob:",
            "media-src 'self'",
            "connect-src 'self' https://api.pusher.com https://*.pusherapp.com wss://*.pusherapp.com",
            "frame-src 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests"
        ]);
    }

    /**
     * Build Permissions Policy.
     */
    protected function buildPermissionsPolicy(): string
    {
        return implode(', ', [
            'accelerometer=()',
            'ambient-light-sensor=()',
            'autoplay=()',
            'battery=()',
            'camera=()',
            'cross-origin-isolated=()',
            'display-capture=()',
            'document-domain=()',
            'encrypted-media=()',
            'execution-while-not-rendered=()',
            'execution-while-out-of-viewport=()',
            'fullscreen=()',
            'geolocation=()',
            'gyroscope=()',
            'keyboard-map=()',
            'magnetometer=()',
            'microphone=()',
            'midi=()',
            'navigation-override=()',
            'payment=()',
            'picture-in-picture=()',
            'publickey-credentials-get=()',
            'screen-wake-lock=()',
            'sync-xhr=()',
            'usb=()',
            'web-share=()',
            'xr-spatial-tracking=()'
        ]);
    }

    /**
     * Build Feature Policy (legacy).
     */
    protected function buildFeaturePolicy(): string
    {
        return implode('; ', [
            "accelerometer 'none'",
            "ambient-light-sensor 'none'",
            "autoplay 'none'",
            "battery 'none'",
            "camera 'none'",
            "display-capture 'none'",
            "document-domain 'none'",
            "encrypted-media 'none'",
            "execution-while-not-rendered 'none'",
            "execution-while-out-of-viewport 'none'",
            "fullscreen 'none'",
            "geolocation 'none'",
            "gyroscope 'none'",
            "magnetometer 'none'",
            "microphone 'none'",
            "midi 'none'",
            "payment 'none'",
            "picture-in-picture 'none'",
            "publickey-credentials-get 'none'",
            "screen-wake-lock 'none'",
            "sync-xhr 'none'",
            "usb 'none'",
            "web-share 'none'",
            "xr-spatial-tracking 'none'"
        ]);
    }

    /**
     * Check if current route is sensitive.
     */
    protected function isSensitiveRoute(): bool
    {
        $request = request();
        $sensitiveRoutes = [
            'api/login',
            'api/register',
            'api/password/*',
            'api/me',
            'api/notifications/*',
        ];

        foreach ($sensitiveRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if current route is logout.
     */
    protected function isLogoutRoute(): bool
    {
        return request()->is('api/logout');
    }

    /**
     * Get security headers configuration.
     */
    public static function getSecurityHeaders(): array
    {
        return [
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
        ];
    }
} 