<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(): JsonResponse
    {
        $sections = Section::with('division')->get();
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

        $section->update($validated);
        return response()->json(['message' => 'Section updated successfully', 'section' => $section]);
    }

    public function destroy(Section $section): JsonResponse
    {
        $section->delete();
        return response()->json(['message' => 'Section deleted successfully']);
    }
}
