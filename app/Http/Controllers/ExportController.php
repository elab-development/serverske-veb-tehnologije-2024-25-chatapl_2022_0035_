<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    /**
     * Export messages from a room to CSV
     */
    public function exportMessages(Request $request, string $roomId): JsonResponse
    {
        $room = Room::findOrFail($roomId);
        
        // Check if user is in the room
        if (!$room->users()->where('user_id', $request->user()->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this room'
            ], 403);
        }

        $messages = Message::with('user')
            ->where('room_id', $roomId)
            ->orderBy('created_at', 'asc')
            ->get();

        $filename = "messages_room_{$roomId}_" . date('Y-m-d_H-i-s') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($messages) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, ['ID', 'User', 'Content', 'Type', 'Created At']);
            
            // CSV data
            foreach ($messages as $message) {
                fputcsv($file, [
                    $message->id,
                    $message->user->name,
                    $message->content,
                    $message->type,
                    $message->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export room statistics to CSV
     */
    public function exportRoomStats(Request $request, string $roomId): JsonResponse
    {
        $room = Room::findOrFail($roomId);
        
        // Check if user is admin
        $userRole = $room->users()->where('user_id', $request->user()->id)->first()->pivot->role ?? null;
        
        if ($userRole !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can export room statistics.'
            ], 403);
        }

        $stats = [
            'room_name' => $room->name,
            'total_messages' => $room->messages()->count(),
            'total_users' => $room->users()->count(),
            'created_at' => $room->created_at->format('Y-m-d H:i:s'),
            'last_message_at' => $room->messages()->latest()->first()?->created_at->format('Y-m-d H:i:s') ?? 'No messages',
        ];

        $filename = "room_stats_{$roomId}_" . date('Y-m-d_H-i-s') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($stats) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, ['Metric', 'Value']);
            
            // CSV data
            foreach ($stats as $metric => $value) {
                fputcsv($file, [$metric, $value]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
