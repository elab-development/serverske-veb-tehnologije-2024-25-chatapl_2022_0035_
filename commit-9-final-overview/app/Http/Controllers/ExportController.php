<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ExportController extends Controller
{
    /**
     * Export poruka iz sobe u CSV format
     */
    public function exportMessages(string $roomId): JsonResponse
    {
        $room = Room::findOrFail($roomId);

        // Provera da li je korisnik u sobi
        if (!$room->users()->where('user_id', request()->user()->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this room'
            ], 403);
        }

        $messages = Message::with('user')
            ->where('room_id', $roomId)
            ->orderBy('created_at', 'asc')
            ->get();

        $csvData = [];
        $csvData[] = ['ID', 'Korisnik', 'Sadržaj', 'Tip', 'Datum kreiranja'];

        foreach ($messages as $message) {
            $csvData[] = [
                $message->id,
                $message->user->name,
                $message->content,
                $message->type,
                $message->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'messages_' . $room->name . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        // U realnoj aplikaciji bi se fajl sačuvao na disk ili poslao kao download
        $csvContent = $this->arrayToCsv($csvData);

        return response()->json([
            'success' => true,
            'message' => 'Messages exported successfully',
            'data' => [
                'filename' => $filename,
                'total_messages' => $messages->count(),
                'csv_content' => $csvContent,
                'download_url' => '/api/export/download/' . $filename // Simulacija download linka
            ]
        ]);
    }

    /**
     * Export statistika sobe u CSV format
     */
    public function exportRoomStats(string $roomId): JsonResponse
    {
        $room = Room::findOrFail($roomId);

        // Provera da li je korisnik u sobi
        if (!$room->users()->where('user_id', request()->user()->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this room'
            ], 403);
        }

        $stats = [
            'room_name' => $room->name,
            'total_users' => $room->users()->count(),
            'total_messages' => $room->messages()->count(),
            'messages_today' => $room->messages()->whereDate('created_at', today())->count(),
            'messages_this_week' => $room->messages()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'messages_this_month' => $room->messages()->whereMonth('created_at', now()->month)->count(),
            'created_at' => $room->created_at->format('Y-m-d H:i:s'),
            'last_message_at' => $room->messages()->latest()->first()?->created_at->format('Y-m-d H:i:s') ?? 'Nema poruka',
        ];

        $csvData = [];
        $csvData[] = ['Metrika', 'Vrednost'];
        
        foreach ($stats as $key => $value) {
            $csvData[] = [$key, $value];
        }

        $filename = 'room_stats_' . $room->name . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $csvContent = $this->arrayToCsv($csvData);

        return response()->json([
            'success' => true,
            'message' => 'Room statistics exported successfully',
            'data' => [
                'filename' => $filename,
                'stats' => $stats,
                'csv_content' => $csvContent,
                'download_url' => '/api/export/download/' . $filename
            ]
        ]);
    }

    /**
     * Pomoćna funkcija za konverziju niza u CSV format
     */
    private function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
