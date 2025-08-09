<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Events\UserJoinedRoom;
use App\Events\UserLeftRoom;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class RoomController extends Controller
{
    /**
     * Prikaz liste resursa.
     */
    public function index(Request $request): JsonResponse
    {
        // Kreiranje keš ključa na osnovu parametara zahteva
        $cacheKey = 'rooms_' . md5(serialize($request->all()));
        
        // Pokušaj dohvatanja iz keša prvo
        $rooms = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = Room::with('users')->where('is_active', true);

            // Filtriranje po tipu
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filtriranje po nazivu (pretraga)
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
            }

            // Filtriranje po broju korisnika
            if ($request->has('min_users')) {
                $query->whereHas('users', function($q) use ($request) {
                    $q->havingRaw('COUNT(*) >= ?', [$request->min_users]);
                });
            }

            // Sortiranje po
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginacija
            $perPage = $request->get('per_page', 10);
            return $query->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    /**
     * Čuvanje novog resursa u skladištu.
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

        // Dodavanje kreatora kao admina
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
     * Prikaz određenog resursa.
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
     * Ažuriranje određenog resursa u skladištu.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $room = Room::findOrFail($id);

        // Provera da li je korisnik admin
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
     * Uklanjanje određenog resursa iz skladišta.
     */
    public function destroy(string $id): JsonResponse
    {
        $room = Room::findOrFail($id);

        // Provera da li je korisnik admin
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
     * Pridruživanje sobi.
     */
    public function join(Request $request, string $id): JsonResponse
    {
        $room = Room::findOrFail($id);
        $user = $request->user();

        // Provera da li je korisnik već u sobi
        if ($room->users()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'User is already in this room'
            ], 400);
        }

        // Provera da li je soba puna
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

        // Brisanje keša sobe za osiguravanje svežih podataka
        Cache::flush();

        // Broadcast događaja ulaska korisnika (samo ako je broadcasting konfigurisan)
        try {
            broadcast(new UserJoinedRoom($user, $room))->toOthers();
        } catch (\Exception $e) {
            // Logovanje greške ali ne propadanje zahteva
            \Log::warning('Broadcasting failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Joined room successfully',
            'data' => $room->load('users')
        ]);
    }

    /**
     * Napuštanje sobe.
     */
    public function leave(Request $request, string $id): JsonResponse
    {
        $room = Room::findOrFail($id);
        $user = $request->user();

        $room->users()->detach($user->id);

        // Broadcast događaja izlaska korisnika (samo ako je broadcasting konfigurisan)
        try {
            broadcast(new UserLeftRoom($user, $room))->toOthers();
        } catch (\Exception $e) {
            // Logovanje greške ali ne propadanje zahteva
            \Log::warning('Broadcasting failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Left room successfully'
        ]);
    }
}
