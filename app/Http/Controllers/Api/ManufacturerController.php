<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manufacturer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManufacturerController extends Controller
{
    /**
     * List all manufacturers for the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $manufacturers = Manufacturer::withCount('items')
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $manufacturers,
        ]);
    }

    /**
     * Create a new manufacturer.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $manufacturer = Manufacturer::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Manufacturer created successfully.',
            'data'    => $manufacturer,
        ], 201);
    }

    /**
     * Get a specific manufacturer.
     */
    public function show(int $id): JsonResponse
    {
        $manufacturer = Manufacturer::withCount('items')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $manufacturer,
        ]);
    }

    /**
     * Update a manufacturer.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $manufacturer = Manufacturer::findOrFail($id);
        $manufacturer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Manufacturer updated successfully.',
            'data'    => $manufacturer->fresh(),
        ]);
    }

    /**
     * Delete a manufacturer.
     */
    public function destroy(int $id): JsonResponse
    {
        $manufacturer = Manufacturer::withCount('items')->findOrFail($id);

        if ($manufacturer->items_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete manufacturer with {$manufacturer->items_count} associated items.",
            ], 422);
        }

        $manufacturer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Manufacturer deleted successfully.',
        ]);
    }
}
