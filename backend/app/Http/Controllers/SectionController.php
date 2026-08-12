<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'division_id' => 'sometimes|required|integer|exists:divisions,id',
        ]);

        $sections = Section::with('division')
            ->when(
                isset($validated['division_id']),
                fn ($query) => $query->where('division_id', $validated['division_id'])
            )
            ->orderBy('name')
            ->get();

        return response()->json($sections);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'division_id' => 'nullable|exists:divisions,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $section = Section::create($validated);
        $this->writeLog($request, 'CREATE', "Section #{$section->id} created: {$section->name}.");
        return response()->json(['message' => 'Section created successfully', 'section' => $section], 201);
    }

    public function show(Section $section): JsonResponse
    {
        return response()->json($section->load('division'));
    }

    public function update(Request $request, Section $section): JsonResponse
    {
        $validated = $request->validate([
            'division_id' => 'nullable|exists:divisions,id',
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $original = $section->only(array_keys($validated));
        $section->update($validated);
        $changes = $this->describeChanges($section->getChanges(), $original);
        if ($changes) {
            $this->writeLog($request, 'UPDATE', "Section #{$section->id} updated: " . implode(', ', $changes) . '.');
        }
        return response()->json(['message' => 'Section updated successfully', 'section' => $section]);
    }

    public function destroy(Request $request, Section $section): JsonResponse
    {
        $message = "Section #{$section->id} deleted: {$section->name}.";
        $section->delete();
        $this->writeLog($request, 'DELETE', $message);
        return response()->json(['message' => 'Section deleted successfully']);
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
