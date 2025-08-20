<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditController extends Controller
{
    /**
     * Get audit logs with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'action' => 'nullable|string|max:255',
            'resource_type' => 'nullable|string|max:255',
            'resource_id' => 'nullable|integer',
            'ip_address' => 'nullable|ip',
            'status' => 'nullable|in:success,failed,warning',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $filters = $request->only([
            'user_id', 'action', 'resource_type', 'resource_id',
            'ip_address', 'status', 'date_from', 'date_to'
        ]);

        $perPage = $request->get('per_page', 20);
        $logs = AuditService::getLogs($filters)->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem()
            ]
        ]);
    }

    /**
     * Get security statistics.
     */
    public function securityStats(): JsonResponse
    {
        $stats = AuditService::getSecurityStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get audit log by ID.
     */
    public function show(int $id): JsonResponse
    {
        $log = \App\Models\AuditLog::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $log
        ]);
    }

    /**
     * Get recent suspicious activities.
     */
    public function suspiciousActivities(): JsonResponse
    {
        $activities = \App\Models\AuditLog::with('user')
            ->withStatus('warning')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    /**
     * Get failed login attempts.
     */
    public function failedLogins(Request $request): JsonResponse
    {
        $request->validate([
            'hours' => 'nullable|integer|min:1|max:168', // max 7 days
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $hours = $request->get('hours', 24);
        $perPage = $request->get('per_page', 20);

        $failedLogins = \App\Models\AuditLog::with('user')
            ->forAction('login_failed')
            ->withStatus('failed')
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $failedLogins->items(),
            'pagination' => [
                'current_page' => $failedLogins->currentPage(),
                'last_page' => $failedLogins->lastPage(),
                'per_page' => $failedLogins->perPage(),
                'total' => $failedLogins->total()
            ]
        ]);
    }

    /**
     * Export audit logs to CSV.
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'action' => 'nullable|string',
            'status' => 'nullable|in:success,failed,warning'
        ]);

        $filters = $request->only(['date_from', 'date_to', 'action', 'status']);
        $logs = AuditService::getLogs($filters)->get();

        $csvData = [];
        $csvData[] = ['ID', 'User', 'Action', 'Resource Type', 'Resource ID', 'IP Address', 'Status', 'Created At'];

        foreach ($logs as $log) {
            $csvData[] = [
                $log->id,
                $log->user ? $log->user->name : 'Anonymous',
                $log->action,
                $log->resource_type,
                $log->resource_id,
                $log->ip_address,
                $log->status,
                $log->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filepath = storage_path('app/public/exports/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $file = fopen($filepath, 'w');
        foreach ($csvData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        return response()->json([
            'success' => true,
            'message' => 'Audit logs exported successfully',
            'data' => [
                'filename' => $filename,
                'download_url' => url('storage/exports/' . $filename),
                'total_records' => count($logs)
            ]
        ]);
    }
} 