<?php

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function index(): JsonResponse
    {
        $offices = Office::with(['division', 'sections'])->get();
        return response()->json($offices);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'division_id' => 'nullable|exists:divisions,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $office = Office::create($validated);
        return response()->json(['message' => 'Office created successfully', 'office' => $office], 201);
    }

    public function show(Office $office): JsonResponse
    {
        return response()->json($office->load(['division', 'sections']));
    }

    public function update(Request $request, Office $office): JsonResponse
    {
        $validated = $request->validate([
            'division_id' => 'nullable|exists:divisions,id',
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $office->update($validated);
        return response()->json(['message' => 'Office updated successfully', 'office' => $office]);
    }

    public function destroy(Office $office): JsonResponse
    {
        $office->delete();
        return response()->json(['message' => 'Office deleted successfully']);
    }
}
