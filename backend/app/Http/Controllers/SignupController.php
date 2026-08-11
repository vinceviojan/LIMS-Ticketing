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
            'division' => ['required', 'string', 'max:255'],
            'sections' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'division' => $data['division'],
            'sections' => $data['sections'],
            'position' => $data['position'],
            'role' => 'USER',
            'status' => 'ACTIVE',
        ]);

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
