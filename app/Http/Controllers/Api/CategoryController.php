<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * List all categories for the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = Category::withCount('items')
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }

    /**
     * Create a new category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data'    => $category,
        ], 201);
    }

    /**
     * Get a specific category.
     */
    public function show(int $id): JsonResponse
    {
        $category = Category::withCount('items')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $category,
        ]);
    }

    /**
     * Update a category.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $category = Category::findOrFail($id);
        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data'    => $category->fresh(),
        ]);
    }

    /**
     * Delete a category.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = Category::withCount('items')->findOrFail($id);

        if ($category->items_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete category with {$category->items_count} associated items.",
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }
}
