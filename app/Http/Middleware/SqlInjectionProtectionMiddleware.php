<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SqlInjectionProtectionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for SQL injection patterns in request data
        if ($this->detectSqlInjection($request)) {
            Log::warning('Potential SQL injection attempt detected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid request detected.',
                'error_code' => 'SECURITY_VIOLATION'
            ], 400);
        }

        return $next($request);
    }

    /**
     * Detect potential SQL injection patterns.
     */
    protected function detectSqlInjection(Request $request): bool
    {
        $input = $request->all();
        return $this->checkArrayForSqlInjection($input);
    }

    /**
     * Recursively check array for SQL injection patterns.
     */
    protected function checkArrayForSqlInjection(array $data): bool
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->checkArrayForSqlInjection($value)) {
                    return true;
                }
            } else {
                if ($this->isSqlInjectionPattern($value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a value contains SQL injection patterns.
     */
    protected function isSqlInjectionPattern($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $value = strtolower($value);

        // Common SQL injection patterns
        $patterns = [
            // SQL keywords
            '/\b(union|select|insert|update|delete|drop|create|alter|exec|execute)\b/i',
            '/\b(where|from|into|values|set|table|database|schema)\b/i',
            
            // SQL operators
            '/\b(and|or|not|xor|like|in|between|exists)\b/i',
            
            // SQL functions
            '/\b(count|sum|avg|max|min|concat|substring|char|ascii)\b/i',
            
            // SQL comments
            '/--/',
            '/\/\*/',
            '/\*\//',
            
            // SQL string termination
            '/\'.*\'/',
            '/".*"/',
            
            // SQL injection techniques
            '/\b(1=1|1=0|true|false)\b/i',
            '/\b(union\s+select|union\s+all\s+select)\b/i',
            '/\b(select\s+.*\s+from)\b/i',
            '/\b(insert\s+into\s+.*\s+values)\b/i',
            '/\b(update\s+.*\s+set)\b/i',
            '/\b(delete\s+from)\b/i',
            '/\b(drop\s+table|drop\s+database)\b/i',
            '/\b(create\s+table|create\s+database)\b/i',
            
            // Blind SQL injection
            '/\b(if\s*\(|case\s+when|when\s+.*\s+then)\b/i',
            
            // Time-based SQL injection
            '/\b(sleep\s*\(|benchmark\s*\(|waitfor\s+delay)\b/i',
            
            // Error-based SQL injection
            '/\b(extractvalue|updatexml|floor\s*\(|rand\s*\(|exp\s*\()\b/i',
            
            // Stacked queries
            '/;/',
            '/\b(begin|end|go|delimiter)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log SQL injection attempt details.
     */
    protected function logSqlInjectionAttempt(Request $request, string $suspiciousValue): void
    {
        Log::critical('SQL injection attempt detected', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'suspicious_value' => $suspiciousValue,
            'user_id' => $request->user()?->id,
            'timestamp' => now(),
            'headers' => $request->headers->all(),
        ]);
    }

    /**
     * Get suspicious SQL patterns for monitoring.
     */
    public static function getSuspiciousPatterns(): array
    {
        return [
            'union select',
            'drop table',
            'insert into',
            'update set',
            'delete from',
            'create table',
            'alter table',
            'exec(',
            'execute(',
            '1=1',
            '1=0',
            'true',
            'false',
            '--',
            '/*',
            '*/',
            ';',
        ];
    }
} 