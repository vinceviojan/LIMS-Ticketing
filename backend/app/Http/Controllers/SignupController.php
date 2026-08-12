<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SignupController extends Controller
{
    /**
     * Register a new user account.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'position' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'division_id' => $data['division_id'] ?? null,
            'section_id' => $data['section_id'] ?? null,
            'position' => $data['position'],
            'role' => 'USER',
            'status' => 'ACTIVE',
        ]);

        $user->load(['division', 'section']);

        Log::create([
            'user_id' => $user->id,
            'action' => 'CREATE',
            'message' => "User #{$user->id} registered: {$user->first_name} {$user->last_name} ({$user->email}), role USER.",
            'address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $user,
        ], 201);
    }
}
