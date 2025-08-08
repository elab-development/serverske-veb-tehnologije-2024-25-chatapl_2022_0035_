<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Room;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

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

        $messages = Message::with('user')
            ->where('room_id', $request->room_id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

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

        // Broadcast the message to all users in the room
        broadcast(new MessageSent($message))->toOthers();

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
}
