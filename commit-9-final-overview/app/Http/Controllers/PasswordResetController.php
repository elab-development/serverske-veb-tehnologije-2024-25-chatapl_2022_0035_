<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Slanje linka za resetovanje lozinke
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // Generisanje tokena za resetovanje
        $token = Str::random(60);
        $user->update([
            'password_reset_token' => $token,
            'password_reset_expires_at' => now()->addHours(1), // Token važi 1 sat
        ]);

        // Ovde bi se slao email sa linkom za resetovanje
        // Mail::to($user->email)->send(new PasswordResetMail($user, $token));

        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent to your email',
            'data' => [
                'token' => $token, // U produkciji se ne bi slao token
                'expires_at' => $user->password_reset_expires_at
            ]
        ]);
    }

    /**
     * Resetovanje lozinke sa tokenom
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)
                   ->where('password_reset_token', $request->token)
                   ->where('password_reset_expires_at', '>', now())
                   ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token'
            ], 400);
        }

        // Ažuriranje lozinke i brisanje tokena
        $user->update([
            'password' => Hash::make($request->password),
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully'
        ]);
    }

    /**
     * Promena lozinke za autentifikovanog korisnika
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Provera trenutne lozinke
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        // Ažuriranje lozinke
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }
}
