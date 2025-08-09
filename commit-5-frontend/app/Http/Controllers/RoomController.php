<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Events\UserJoinedRoom;
use App\Events\UserLeftRoom;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $rooms = Room::with('users')->where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:public,private',
            'max_users' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $room = Room::create($request->all());

        // Add creator as admin
        $room->users()->attach($request->user()->id, [
            'role' => 'admin',
            'is_online' => true,
            'last_seen_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Room created successfully',
            'data' => $room->load('users')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $room = Room::with(['users', 'messages.user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $room
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $room = Room::findOrFail($id);

        // Check if user is admin
        $userRole = $room->users()->where('user_id', $request->user()->id)->first()->pivot->role ?? null;
        
        if ($userRole !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can update rooms.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:public,private',
            'max_users' => 'nullable|integer|min:1|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $room->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Room updated successfully',
            'data' => $room->load('users')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $room = Room::findOrFail($id);

        // Check if user is admin
        $userRole = $room->users()->where('user_id', request()->user()->id)->first()->pivot->role ?? null;
        
        if ($userRole !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can delete rooms.'
            ], 403);
        }

        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room deleted successfully'
        ]);
    }

    /**
     * Join a room.
     */
    public function join(Request $request, string $id): JsonResponse
    {
        $room = Room::findOrFail($id);
        $user = $request->user();

        // Check if user is already in the room
        if ($room->users()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'User is already in this room'
            ], 400);
        }

        // Check if room is full
        if ($room->users()->count() >= $room->max_users) {
            return response()->json([
                'success' => false,
                'message' => 'Room is full'
            ], 400);
        }

        $room->users()->attach($user->id, [
            'role' => 'member',
            'is_online' => true,
            'last_seen_at' => now()
        ]);

        // Broadcast user joined event
        broadcast(new UserJoinedRoom($user, $room))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Joined room successfully',
            'data' => $room->load('users')
        ]);
    }

    /**
     * Leave a room.
     */
    public function leave(Request $request, string $id): JsonResponse
    {
        $room = Room::findOrFail($id);
        $user = $request->user();

        $room->users()->detach($user->id);

        // Broadcast user left event
        broadcast(new UserLeftRoom($user, $room))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Left room successfully'
        ]);
    }
}
