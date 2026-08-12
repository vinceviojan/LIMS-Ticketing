<?php

namespace App\Http\Controllers;

use App\Models\Division;
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

        $division->update($validated);
        return response()->json(['message' => 'Division updated successfully', 'division' => $division]);
    }

    public function destroy(Division $division): JsonResponse
    {
        $division->delete();
        return response()->json(['message' => 'Division deleted successfully']);
    }
}
