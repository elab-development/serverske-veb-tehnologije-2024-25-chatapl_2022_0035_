<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * Dohvatanje ukupnih statistika aplikacije
     */
    public function overall(Request $request): JsonResponse
    {
        $cacheKey = 'statistics_overall';
        
        $statistics = Cache::remember($cacheKey, 300, function () {
            return [
                'total_users' => User::count(),
                'total_rooms' => Room::count(),
                'total_messages' => Message::count(),
                'active_rooms' => Room::where('is_active', true)->count(),
                'messages_today' => Message::whereDate('created_at', today())->count(),
                'messages_this_week' => Message::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'messages_this_month' => Message::whereMonth('created_at', now()->month)->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Dohvatanje statistika soba
     */
    public function roomStats(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'room_id' => 'sometimes|exists:rooms,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $cacheKey = 'statistics_room_' . ($request->room_id ?? 'all');
        
        $statistics = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = Room::withCount(['users', 'messages']);
            
            if ($request->has('room_id')) {
                $query->where('id', $request->room_id);
            }
            
            $rooms = $query->get();
            
            $stats = [];
            foreach ($rooms as $room) {
                $stats[] = [
                    'room_id' => $room->id,
                    'room_name' => $room->name,
                    'total_users' => $room->users_count,
                    'total_messages' => $room->messages_count,
                    'messages_today' => $room->messages()->whereDate('created_at', today())->count(),
                    'messages_this_week' => $room->messages()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'messages_this_month' => $room->messages()->whereMonth('created_at', now()->month)->count(),
                    'created_at' => $room->created_at,
                ];
            }
            
            return $stats;
        });

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Dohvatanje statistika korisnika
     */
    public function userStats(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $cacheKey = 'statistics_user_' . ($request->user_id ?? 'all');
        
        $statistics = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = User::withCount(['messages', 'rooms']);
            
            if ($request->has('user_id')) {
                $query->where('id', $request->user_id);
            }
            
            $users = $query->get();
            
            $stats = [];
            foreach ($users as $user) {
                $stats[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'total_messages' => $user->messages_count,
                    'total_rooms' => $user->rooms_count,
                    'messages_today' => $user->messages()->whereDate('created_at', today())->count(),
                    'messages_this_week' => $user->messages()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'messages_this_month' => $user->messages()->whereMonth('created_at', now()->month)->count(),
                    'joined_at' => $user->created_at,
                    'last_message_at' => $user->messages()->latest()->first()?->created_at,
                ];
            }
            
            return $stats;
        });

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Brisanje svih keševa
     */
    public function clearCache(Request $request): JsonResponse
    {
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Cache cleared successfully'
        ]);
    }
}
