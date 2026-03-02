<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    /**
     * List all expense categories.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = ExpenseCategory::withCount('expenses')
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
     * Create a new expense category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $category = ExpenseCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Expense category created successfully.',
            'data'    => $category,
        ], 201);
    }

    /**
     * Get a specific expense category.
     */
    public function show(int $id): JsonResponse
    {
        $category = ExpenseCategory::withCount('expenses')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $category,
        ]);
    }

    /**
     * Update an expense category.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $category = ExpenseCategory::findOrFail($id);
        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Expense category updated successfully.',
            'data'    => $category->fresh(),
        ]);
    }

    /**
     * Delete an expense category.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = ExpenseCategory::withCount('expenses')->findOrFail($id);

        if ($category->expenses_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete category with {$category->expenses_count} associated expenses.",
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense category deleted successfully.',
        ]);
    }
}
