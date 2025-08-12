<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class StatisticsController extends Controller
{
    /**
     * Get overall application statistics
     */
    public function overall(): JsonResponse
    {
        $stats = Cache::remember('app_stats_overall', 600, function () {
            return [
                'total_users' => User::count(),
                'total_rooms' => Room::where('is_active', true)->count(),
                'total_messages' => Message::count(),
                'active_rooms' => Room::where('is_active', true)->count(),
                'public_rooms' => Room::where('type', 'public')->where('is_active', true)->count(),
                'private_rooms' => Room::where('type', 'private')->where('is_active', true)->count(),
                'messages_today' => Message::whereDate('created_at', today())->count(),
                'messages_this_week' => Message::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get room statistics
     */
    public function roomStats(string $roomId): JsonResponse
    {
        $cacheKey = 'room_stats_' . $roomId;
        
        $stats = Cache::remember($cacheKey, 300, function () use ($roomId) {
            $room = Room::findOrFail($roomId);
            
            return [
                'room_name' => $room->name,
                'total_messages' => $room->messages()->count(),
                'total_users' => $room->users()->count(),
                'messages_today' => $room->messages()->whereDate('created_at', today())->count(),
                'messages_this_week' => $room->messages()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'most_active_user' => $room->messages()
                    ->selectRaw('user_id, COUNT(*) as message_count')
                    ->groupBy('user_id')
                    ->orderBy('message_count', 'desc')
                    ->first(),
                'created_at' => $room->created_at,
                'last_message_at' => $room->messages()->latest()->first()?->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get user statistics
     */
    public function userStats(string $userId): JsonResponse
    {
        $cacheKey = 'user_stats_' . $userId;
        
        $stats = Cache::remember($cacheKey, 300, function () use ($userId) {
            $user = User::findOrFail($userId);
            
            return [
                'user_name' => $user->name,
                'total_messages' => $user->messages()->count(),
                'total_rooms' => $user->rooms()->count(),
                'messages_today' => $user->messages()->whereDate('created_at', today())->count(),
                'messages_this_week' => $user->messages()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'favorite_room' => $user->messages()
                    ->selectRaw('room_id, COUNT(*) as message_count')
                    ->groupBy('room_id')
                    ->orderBy('message_count', 'desc')
                    ->first(),
                'joined_at' => $user->created_at,
                'last_message_at' => $user->messages()->latest()->first()?->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Clear all cache
     */
    public function clearCache(): JsonResponse
    {
        Cache::flush();
        
        return response()->json([
            'success' => true,
            'message' => 'All cache cleared successfully'
        ]);
    }
}
