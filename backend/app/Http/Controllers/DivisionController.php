<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    public function index(): JsonResponse
    {
        $divisions = Division::with('sections')->get();
        return response()->json($divisions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:divisions,code',
            'description' => 'nullable|string',
        ]);

        $division = Division::create($validated);
        $this->writeLog($request, 'CREATE', "Division #{$division->id} created: {$division->name}.");
        return response()->json(['message' => 'Division created successfully', 'division' => $division], 201);
    }

    public function show(Division $division): JsonResponse
    {
        return response()->json($division->load('sections'));
    }

    public function update(Request $request, Division $division): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50|unique:divisions,code,' . $division->id,
            'description' => 'nullable|string',
        ]);

        $original = $division->only(array_keys($validated));
        $division->update($validated);
        $changes = $this->describeChanges($division->getChanges(), $original);
        if ($changes) {
            $this->writeLog($request, 'UPDATE', "Division #{$division->id} updated: " . implode(', ', $changes) . '.');
        }
        return response()->json(['message' => 'Division updated successfully', 'division' => $division]);
    }

    public function destroy(Request $request, Division $division): JsonResponse
    {
        $message = "Division #{$division->id} deleted: {$division->name}.";
        $division->delete();
        $this->writeLog($request, 'DELETE', $message);
        return response()->json(['message' => 'Division deleted successfully']);
    }

    private function describeChanges(array $changes, array $original): array
    {
        unset($changes['updated_at']);
        return collect($changes)->map(fn ($value, $field) =>
            "{$field} changed from '" . ($original[$field] ?? '') . "' to '" . ($value ?? '') . "'"
        )->values()->all();
    }

    private function writeLog(Request $request, string $action, string $message): void
    {
        Log::create(['user_id' => $request->user()->id, 'action' => $action, 'message' => $message, 'address' => $request->ip()]);
    }
}
