<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    /**
     * List items (paginated, filterable by category, manufacturer, status).
     */
    public function index(Request $request): JsonResponse
    {
        $items = Item::with(['category', 'manufacturer'])
            ->withCount('batches')
            ->withSum('batches as total_stock', 'stock_quantity')
            ->when($request->filled('category_id'), function ($q) use ($request) {
                $q->where('category_id', $request->input('category_id'));
            })
            ->when($request->filled('manufacturer_id'), function ($q) use ($request) {
                $q->where('manufacturer_id', $request->input('manufacturer_id'));
            })
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            })
            ->when($request->filled('schedule'), function ($q) use ($request) {
                $q->where('schedule', $request->input('schedule'));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->search($request->input('search'));
            })
            ->orderBy('name')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => ItemResource::collection($items),
            'meta'    => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
            ],
        ]);
    }

    /**
     * Create a new item.
     */
    public function store(StoreItemRequest $request): JsonResponse
    {
        $item = Item::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Item created successfully.',
            'data'    => new ItemResource($item->load(['category', 'manufacturer'])),
        ], 201);
    }

    /**
     * Get item details with batches.
     */
    public function show(int $id): JsonResponse
    {
        $item = Item::with(['category', 'manufacturer', 'batches' => function ($q) {
            $q->where('stock_quantity', '>', 0)->orderBy('expiry_date');
        }])
            ->withCount('batches')
            ->withSum('batches as total_stock', 'stock_quantity')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new ItemResource($item),
        ]);
    }

    /**
     * Update an item.
     */
    public function update(UpdateItemRequest $request, int $id): JsonResponse
    {
        $item = Item::findOrFail($id);
        $item->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully.',
            'data'    => new ItemResource($item->fresh()->load(['category', 'manufacturer'])),
        ]);
    }

    /**
     * Soft delete an item.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = Item::findOrFail($id);

        // Check if item has stock before deleting
        $totalStock = $item->batches()->sum('stock_quantity');
        if ($totalStock > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete item with existing stock ({$totalStock} units remaining).",
            ], 422);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully.',
        ]);
    }

    /**
     * Search items by name, composition, or barcode.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1',
        ]);

        $items = Item::with(['category', 'manufacturer'])
            ->withSum('batches as total_stock', 'stock_quantity')
            ->search($request->input('q'))
            ->where('is_active', true)
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => ItemResource::collection($items),
        ]);
    }

    /**
     * Find item by barcode.
     */
    public function lookupBarcode(string $barcode): JsonResponse
    {
        $item = Item::with(['category', 'manufacturer', 'batches' => function ($q) {
            $q->available()->fifoOrder();
        }])
            ->where('barcode', $barcode)
            ->first();

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found for the given barcode.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new ItemResource($item),
        ]);
    }
}
