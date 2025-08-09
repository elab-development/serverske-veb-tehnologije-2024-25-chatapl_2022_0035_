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
     * Prikaz liste resursa.
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

        // Kreiranje keš ključa na osnovu parametara zahteva
        $cacheKey = 'messages_room_' . $request->room_id . '_' . md5(serialize($request->all()));
        
        // Pokušaj dohvatanja iz keša prvo (kraće vreme keširanja za poruke)
        $messages = Cache::remember($cacheKey, 60, function () use ($request) {
            $query = Message::with('user')->where('room_id', $request->room_id);

            // Filtriranje po korisniku
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filtriranje po tipu
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filtriranje po datumu
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Pretraga u sadržaju
            if ($request->has('search')) {
                $query->where('content', 'like', '%' . $request->search . '%');
            }

            // Sortiranje po
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginacija
            $perPage = $request->get('per_page', 50);
            return $query->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Čuvanje novog resursa u skladištu.
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

        // Provera da li je korisnik u sobi
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

        // Brisanje keša za poruke ove sobe
        Cache::forget('messages_room_' . $request->room_id . '_*');

        // Broadcast poruke svim korisnicima u sobi (samo ako je broadcasting konfigurisan)
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Exception $e) {
            // Logovanje greške ali ne propadanje zahteva
            \Log::warning('Broadcasting failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message->load('user')
        ], 201);
    }

    /**
     * Prikaz određenog resursa.
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
     * Ažuriranje određenog resursa u skladištu.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        // Provera da li korisnik poseduje poruku
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
     * Uklanjanje određenog resursa iz skladišta.
     */
    public function destroy(string $id): JsonResponse
    {
        $message = Message::findOrFail($id);

        // Provera da li korisnik poseduje poruku ili je admin
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
     * Upload fajla i kreiranje poruke
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'file' => 'required|file|max:10240', // 10MB maksimum
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Provera da li je korisnik u sobi
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

        // Broadcast poruke svim korisnicima u sobi
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => $message->load('user')
        ], 201);
    }

    /**
     * Download fajla
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

        // Provera da li je korisnik u sobi
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
