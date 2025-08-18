<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log an action to the audit log.
     */
    public static function log(
        string $action,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?array $details = null,
        string $status = 'success',
        ?Request $request = null
    ): void {
        $request = $request ?? request();
        
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => $details,
            'status' => $status
        ]);
    }

    /**
     * Log a successful action.
     */
    public static function logSuccess(
        string $action,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?array $details = null
    ): void {
        self::log($action, $resourceType, $resourceId, $details, 'success');
    }

    /**
     * Log a failed action.
     */
    public static function logFailure(
        string $action,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?array $details = null
    ): void {
        self::log($action, $resourceType, $resourceId, $details, 'failed');
    }

    /**
     * Log a warning.
     */
    public static function logWarning(
        string $action,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?array $details = null
    ): void {
        self::log($action, $resourceType, $resourceId, $details, 'warning');
    }

    /**
     * Log user authentication events.
     */
    public static function logAuth(string $action, ?array $details = null): void
    {
        self::log($action, 'User', Auth::id(), $details);
    }

    /**
     * Log room-related events.
     */
    public static function logRoom(string $action, int $roomId, ?array $details = null): void
    {
        self::log($action, 'Room', $roomId, $details);
    }

    /**
     * Log message-related events.
     */
    public static function logMessage(string $action, int $messageId, ?array $details = null): void
    {
        self::log($action, 'Message', $messageId, $details);
    }

    /**
     * Get audit logs with filters.
     */
    public static function getLogs(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = AuditLog::with('user');

        if (isset($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->forAction($filters['action']);
        }

        if (isset($filters['resource_type'])) {
            $query->forResource($filters['resource_type'], $filters['resource_id'] ?? null);
        }

        if (isset($filters['ip_address'])) {
            $query->fromIp($filters['ip_address']);
        }

        if (isset($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get security statistics.
     */
    public static function getSecurityStats(): array
    {
        $last24Hours = now()->subDay();
        $last7Days = now()->subWeek();
        $last30Days = now()->subMonth();

        return [
            'failed_logins_24h' => AuditLog::forAction('login_failed')
                ->withStatus('failed')
                ->where('created_at', '>=', $last24Hours)
                ->count(),
            'failed_logins_7d' => AuditLog::forAction('login_failed')
                ->withStatus('failed')
                ->where('created_at', '>=', $last7Days)
                ->count(),
            'failed_logins_30d' => AuditLog::forAction('login_failed')
                ->withStatus('failed')
                ->where('created_at', '>=', $last30Days)
                ->count(),
            'suspicious_activities_24h' => AuditLog::withStatus('warning')
                ->where('created_at', '>=', $last24Hours)
                ->count(),
            'unique_ips_24h' => AuditLog::where('created_at', '>=', $last24Hours)
                ->distinct('ip_address')
                ->count('ip_address'),
            'total_actions_24h' => AuditLog::where('created_at', '>=', $last24Hours)->count(),
        ];
    }
} 