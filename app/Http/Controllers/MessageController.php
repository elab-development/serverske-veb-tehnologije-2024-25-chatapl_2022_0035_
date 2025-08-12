<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Room;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create cache key based on request parameters
        $cacheKey = 'messages_room_' . $request->room_id . '_' . md5(serialize($request->all()));
        
        // Try to get from cache first (shorter cache time for messages)
        $messages = Cache::remember($cacheKey, 60, function () use ($request) {
            $query = Message::with('user')->where('room_id', $request->room_id);

            // Filter by user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search in content
            if ($request->has('search')) {
                $query->where('content', 'like', '%' . $request->search . '%');
            }

            // Sort by
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 50);
            return $query->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'content' => 'required|string|max:1000',
            'type' => 'sometimes|in:text,image,file',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user is in the room
        $room = Room::findOrFail($request->room_id);
        if (!$room->users()->where('user_id', $request->user()->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this room'
            ], 403);
        }

        $message = Message::create([
            'user_id' => $request->user()->id,
            'room_id' => $request->room_id,
            'content' => $request->content,
            'type' => $request->type ?? 'text',
        ]);

        // Clear cache for this room's messages
        Cache::forget('messages_room_' . $request->room_id . '_*');

        // Broadcast the message to all users in the room (only if broadcasting is configured)
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            \Log::warning('Broadcasting failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message->load('user')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $message = Message::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        // Check if user owns the message
        if ($message->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only edit your own messages.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $message->update($request->only('content'));

        return response()->json([
            'success' => true,
            'message' => 'Message updated successfully',
            'data' => $message->load('user')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        // Check if user owns the message or is admin
        $room = $message->room;
        $userRole = $room->users()->where('user_id', request()->user()->id)->first()->pivot->role ?? null;
        
        if ($message->user_id !== request()->user()->id && $userRole !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only delete your own messages or be an admin.'
            ], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }

    /**
     * Upload file and create message
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user is in the room
        $room = Room::findOrFail($request->room_id);
        if (!$room->users()->where('user_id', $request->user()->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this room'
            ], 403);
        }

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('uploads', $fileName, 'public');

        $message = Message::create([
            'user_id' => $request->user()->id,
            'room_id' => $request->room_id,
            'content' => 'File uploaded: ' . $file->getClientOriginalName(),
            'type' => 'file',
            'file_path' => $filePath,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getMimeType(),
        ]);

        // Broadcast the message to all users in the room
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => $message->load('user')
        ], 201);
    }

    /**
     * Download file
     */
    public function downloadFile(string $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        if (!$message->file_path) {
            return response()->json([
                'success' => false,
                'message' => 'No file attached to this message'
            ], 404);
        }

        // Check if user is in the room
        $room = $message->room;
        if (!$room->users()->where('user_id', request()->user()->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this room'
            ], 403);
        }

        if (!Storage::disk('public')->exists($message->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        $filePath = Storage::disk('public')->path($message->file_path);
        
        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => Storage::disk('public')->url($message->file_path),
                'file_name' => $message->file_name,
                'file_size' => $message->file_size,
                'file_type' => $message->file_type,
            ]
        ]);
    }
}
