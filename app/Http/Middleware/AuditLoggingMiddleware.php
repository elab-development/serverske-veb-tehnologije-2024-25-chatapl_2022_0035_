<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
use Symfony\Component\HttpFoundation\Response;

class AuditLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Log the request
        $this->logRequest($request, $response, $duration);
        
        return $response;
    }

    /**
     * Log request details for audit purposes.
     */
    protected function logRequest(Request $request, Response $response, float $duration): void
    {
        try {
            $user = $request->user();
            $route = $request->route();
            
            $logData = [
                'user_id' => $user?->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'route_name' => $route?->getName(),
                'route_parameters' => $route?->parameters() ?? [],
                'request_headers' => $this->sanitizeHeaders($request->headers->all()),
                'request_body' => $this->sanitizeRequestBody($request->all()),
                'response_status' => $response->getStatusCode(),
                'response_size' => strlen($response->getContent()),
                'duration_ms' => round($duration, 2),
                'timestamp' => now(),
                'session_id' => $request->session()?->getId(),
                'is_authenticated' => $user !== null,
                'user_role' => $user?->is_admin ? 'admin' : 'user',
            ];

            // Determine event type based on route and method
            $eventType = $this->determineEventType($request, $response);
            $logData['event_type'] = $eventType;
            
            // Determine severity level
            $severity = $this->determineSeverity($request, $response, $eventType);
            $logData['severity'] = $severity;

            // Log to database for audit trail
            if ($this->shouldLogToDatabase($eventType, $severity)) {
                AuditLog::create($logData);
            }

            // Log to file system for monitoring
            $this->logToFile($logData);

        } catch (\Exception $e) {
            Log::error('Failed to log audit event: ' . $e->getMessage());
        }
    }

    /**
     * Determine event type based on request and response.
     */
    protected function determineEventType(Request $request, Response $response): string
    {
        $method = $request->method();
        $path = $request->path();
        
        // Authentication events
        if (str_contains($path, 'login')) {
            return $response->getStatusCode() === 200 ? 'login_success' : 'login_failed';
        }
        
        if (str_contains($path, 'register')) {
            return $response->getStatusCode() === 201 ? 'registration_success' : 'registration_failed';
        }
        
        if (str_contains($path, 'logout')) {
            return 'logout';
        }
        
        // CRUD events
        if (str_contains($path, 'messages')) {
            return match($method) {
                'POST' => 'message_created',
                'PUT' => 'message_updated',
                'DELETE' => 'message_deleted',
                default => 'message_viewed'
            };
        }
        
        if (str_contains($path, 'rooms')) {
            return match($method) {
                'POST' => 'room_created',
                'PUT' => 'room_updated',
                'DELETE' => 'room_deleted',
                default => 'room_viewed'
            };
        }
        
        // Security events
        if ($response->getStatusCode() === 429) {
            return 'rate_limit_exceeded';
        }
        
        if ($response->getStatusCode() === 419) {
            return 'csrf_token_mismatch';
        }
        
        if ($response->getStatusCode() === 400 && str_contains($response->getContent(), 'SECURITY_VIOLATION')) {
            return 'security_violation';
        }
        
        // File operations
        if (str_contains($path, 'upload')) {
            return 'file_uploaded';
        }
        
        if (str_contains($path, 'download')) {
            return 'file_downloaded';
        }
        
        return 'api_request';
    }

    /**
     * Determine severity level of the event.
     */
    protected function determineSeverity(Request $request, Response $response, string $eventType): string
    {
        // High severity events
        if (in_array($eventType, [
            'security_violation',
            'rate_limit_exceeded',
            'csrf_token_mismatch',
            'login_failed',
            'registration_failed'
        ])) {
            return 'high';
        }
        
        // Medium severity events
        if (in_array($eventType, [
            'message_deleted',
            'room_deleted',
            'file_uploaded',
            'file_downloaded'
        ])) {
            return 'medium';
        }
        
        // Low severity events
        return 'low';
    }

    /**
     * Check if event should be logged to database.
     */
    protected function shouldLogToDatabase(string $eventType, string $severity): bool
    {
        // Always log high severity events
        if ($severity === 'high') {
            return true;
        }
        
        // Log important events
        $importantEvents = [
            'login_success',
            'login_failed',
            'registration_success',
            'logout',
            'message_created',
            'room_created',
            'file_uploaded',
            'security_violation'
        ];
        
        return in_array($eventType, $importantEvents);
    }

    /**
     * Log to file system.
     */
    protected function logToFile(array $logData): void
    {
        $logLevel = match($logData['severity']) {
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'debug'
        };
        
        Log::channel('audit')->$logLevel('Audit event', $logData);
    }

    /**
     * Sanitize request headers for logging.
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-csrf-token',
            'x-api-key'
        ];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = '[REDACTED]';
            }
        }
        
        return $headers;
    }

    /**
     * Sanitize request body for logging.
     */
    protected function sanitizeRequestBody(array $body): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'api_key'
        ];
        
        foreach ($sensitiveFields as $field) {
            if (isset($body[$field])) {
                $body[$field] = '[REDACTED]';
            }
        }
        
        return $body;
    }

    /**
     * Get audit log statistics.
     */
    public static function getAuditStats(): array
    {
        return [
            'total_events' => AuditLog::count(),
            'events_today' => AuditLog::whereDate('created_at', today())->count(),
            'high_severity_events' => AuditLog::where('severity', 'high')->count(),
            'security_violations' => AuditLog::where('event_type', 'security_violation')->count(),
            'failed_logins' => AuditLog::where('event_type', 'login_failed')->count(),
            'rate_limit_exceeded' => AuditLog::where('event_type', 'rate_limit_exceeded')->count(),
        ];
    }
} 