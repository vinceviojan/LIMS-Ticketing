<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProblemCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ProblemCategoryController extends Controller
{
    /**
     * Display all problem categories.
     */
    public function getAll(): JsonResponse
    {
        $categories = ProblemCategory::all();

        return response()->json([
            'success' => true,
            'message' => 'Problem categories retrieved successfully.',
            'data' => $categories,
        ]);
    }

    /**
     * Display problem categories filtered by type.
     */
    public function byType(string $type): JsonResponse
    {
        $categories = ProblemCategory::where('type', $type)
            ->orderBy('categories')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Problem categories retrieved successfully.',
            'data' => $categories,
        ]);
    }

     public function getCount(string $type): JsonResponse
    {
        // auth:sanctum middleware already blocks unauthenticated requests,
        // but this is a defensive check in case the method is called elsewhere.
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user = Auth::user();

        $count = ProblemCategory::where('type', $type)->count();

        return response()->json([
            'success' => true,
            'type' => $type,
            'count' => $count,
            'requested_by' => $user->id, // optional: track who requested it
        ]);
    }


    /**
     * Count problem categories by type.
     */
    public function countByType(string $type): JsonResponse
    {
        $count = ProblemCategory::where('type', $type)->count();

        return response()->json([
            'success' => true,
            'type' => $type,
            'count' => $count,
        ]);
    }

    /**
     * Display a single problem category by ID.
     */
    public function show(int $id): JsonResponse
    {
        $category = ProblemCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Problem category not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Problem category retrieved successfully.',
            'data' => $category,
        ]);
    }

    /**
     * Store a newly created problem category.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type'       => 'required|string|max:255',
            'categories' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $category = ProblemCategory::create($request->only(['type', 'categories']));

        return response()->json([
            'success' => true,
            'message' => 'Problem category created successfully.',
            'data' => $category,
        ], 201);
    }

    /**
     * Update an existing problem category.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = ProblemCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Problem category not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'type'       => 'sometimes|required|string|max:255',
            'categories' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $category->update($request->only(['type', 'categories']));

        return response()->json([
            'success' => true,
            'message' => 'Problem category updated successfully.',
            'data' => $category,
        ]);
    }

    /**
     * Remove a problem category.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = ProblemCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Problem category not found.',
            ], 404);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Problem category deleted successfully.',
        ]);
    }
}