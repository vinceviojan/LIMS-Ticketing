<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * Supports optional ?search= for name/email filtering.
     */
    public function index(Request $request)
    {
        $query = User::with(['division', 'section']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('last_name')->get();

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Normalise status & role to UPPERCASE before validation
        $input = $request->all();
        if (isset($input['status']))
            $input['status'] = strtoupper($input['status']);
        if (isset($input['role']))
            $input['role'] = strtoupper($input['role']);

        $validator = Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'status' => ['nullable', 'string', 'in:ACTIVE,INACTIVE,SUSPENDED,ARCHIVED'],
            'role' => ['nullable', 'string', 'in:USER,STAFF,ADMIN'],
            'position' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        $user->load(['division', 'section']);

        $this->writeLog($request, 'CREATE', "User #{$user->id} created: {$user->first_name} {$user->last_name} ({$user->email}), role {$user->role}.");

        return response()->json($user, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json($user->load(['division', 'section']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Normalise status & role to UPPERCASE before validation
        $input = $request->all();
        if (isset($input['status']))
            $input['status'] = strtoupper($input['status']);
        if (isset($input['role']))
            $input['role'] = strtoupper($input['role']);

        $validator = Validator::make($input, [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'current_password' => ['sometimes', 'required', 'current_password:sanctum'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'status' => ['nullable', 'string', 'in:ACTIVE,INACTIVE,SUSPENDED,ARCHIVED'],
            'role' => ['nullable', 'string', 'in:USER,STAFF,ADMIN'],
            'position' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $passwordChanged = !empty($data['password']);
        unset($data['current_password']);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (isset($data['first_name']) || isset($data['last_name'])) {
            $data['name'] = trim(
                ($data['first_name'] ?? $user->first_name) . ' ' . ($data['last_name'] ?? $user->last_name)
            );
        }

        $trackedFields = ['first_name', 'last_name', 'email', 'division_id', 'section_id', 'status', 'role', 'position'];
        $original = $user->only($trackedFields);
        $user->update($data);
        $user->load(['division', 'section']);

        $changes = [];
        foreach ($user->getChanges() as $field => $value) {
            if (in_array($field, $trackedFields, true)) {
                $changes[] = "{$field} changed from '" . ($original[$field] ?? '') . "' to '" . ($value ?? '') . "'";
            }
        }
        if ($passwordChanged) {
            $changes[] = 'password changed';
        }
        if ($changes) {
            $this->writeLog($request, 'UPDATE', "User #{$user->id} ({$user->email}) updated: " . implode(', ', $changes) . '.');
        }

        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        $message = "User #{$user->id} deleted: {$user->first_name} {$user->last_name} ({$user->email}), role {$user->role}.";
        $this->writeLog($request, 'DELETE', $message);
        $user->delete();

        return response()->json(null, 204);
    }

    private function writeLog(Request $request, string $action, string $message): void
    {
        Log::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'message' => $message,
            'address' => $request->ip(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $input = $request->all();

        $validator = Validator::make($input, [
            'old_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8'],
            'confirm_password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        if ($input['password'] !== $input['confirm_password']) {
            return response()->json(['errors' => 'Password and confirm password does not match'], 400);
        }

        $data = $validator->validated();
        $data['password'] = Hash::make($data['password']);

        $user->update($data);

        $this->writeLog(
            $request,
            'UPDATE',
            "User #{$user->id} ({$user->email}) updated: password changed."
        );

        return response()->json($user);
    }
}
