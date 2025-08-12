<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export messages from a room to CSV
     */
    public function exportMessages(Request $request, string $roomId): StreamedResponse
    {
        try {
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
                        $message->user->name ?? 'Unknown User',
                        $message->content,
                        $message->type ?? 'text',
                        $message->created_at->format('Y-m-d H:i:s')
                    ]);
                }
                
                fclose($file);
            };

            return new StreamedResponse($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting messages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export room statistics to CSV
     */
    public function exportRoomStats(Request $request, string $roomId): StreamedResponse
    {
        try {
            $room = Room::findOrFail($roomId);
            
            // Check if user is in the room
            if (!$room->users()->where('user_id', $request->user()->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a member of this room'
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

            return new StreamedResponse($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting room statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
