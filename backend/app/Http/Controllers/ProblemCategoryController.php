<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProblemCategory;
use App\Models\Log;
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
        $this->writeLog(
            $request,
            'CREATE',
            "Problem category #{$category->id} created: {$category->type} / {$category->categories}."
        );

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

        $original = $category->only(['type', 'categories']);
        $category->update($request->only(['type', 'categories']));

        $changes = [];
        foreach ($category->getChanges() as $field => $value) {
            if (in_array($field, ['type', 'categories'], true)) {
                $changes[] = "{$field} changed from '{$original[$field]}' to '{$value}'";
            }
        }

        if ($changes) {
            $this->writeLog(
                $request,
                'UPDATE',
                "Problem category #{$category->id} updated: " . implode(', ', $changes) . '.'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Problem category updated successfully.',
            'data' => $category,
        ]);
    }

    /**
     * Remove a problem category.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $category = ProblemCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Problem category not found.',
            ], 404);
        }

        $categoryId = $category->id;
        $categoryType = $category->type;
        $categoryName = $category->categories;
        $category->delete();

        $this->writeLog(
            $request,
            'DELETE',
            "Problem category #{$categoryId} deleted: {$categoryType} / {$categoryName}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Problem category deleted successfully.',
        ]);
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
}
