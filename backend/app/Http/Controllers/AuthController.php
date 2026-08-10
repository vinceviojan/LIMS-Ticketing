<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Log;
use App\Models\Ticket;

class AuthController extends Controller
{
    /**
     * Handle user login and issue a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $user = Auth::user();

        // Check if the user account is active
        if ($user->status !== 'ACTIVE') {
            Auth::logout();

            return response()->json([
                'message' => 'Your account is not active. Please contact an administrator.',
            ], 403);
        }

        // Revoke all existing tokens and issue a new one
        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->writeLog($request, $user, null, 'LOGIN', "{$user->name} has logged in.");
        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
                'division' => $user->division,
                'sections' => $user->sections,
                'position' => $user->position,
                'status' => $user->status,
            ],
        ], 200);
    }

    /**
     * Log out the authenticated user (revoke current token).
     */
    public function logout(Request $request): JsonResponse
    {
        $name = $request->user()->name;
        $request->user()->currentAccessToken()->delete();

        $this->writeLog($request, $request->user(), null, 'LOGOUT', "{$name} has logged out.");

        return response()->json([
            'message' => 'Logged out successfully.',
        ], 200);
    }

    /**
     * Return the currently authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
                'division' => $user->division,
                'sections' => $user->sections,
                'position' => $user->position,
                'status' => $user->status,
            ],
        ], 200);
    }

    private function writeLog(Request $request, $user, $ticket, string $action, string $message): void
    {
        Log::create([
            'user_id' => $user->id,
            'ticket_id' => $ticket != null ? $ticket->id : null,
            'action' => $action,
            'message' => $message,
            'address' => $request->ip(),
        ]);
    }

}
