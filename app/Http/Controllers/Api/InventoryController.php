<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        private readonly StockService $stockService,
    ) {}

    /**
     * Current stock summary (paginated, searchable).
     */
    public function stock(Request $request): JsonResponse
    {
        $items = Item::with(['category:id,name', 'manufacturer:id,name'])
            ->withSum('batches as total_stock', 'stock_quantity')
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->search($request->input('search'));
            })
            ->when($request->filled('category_id'), function ($q) use ($request) {
                $q->where('category_id', $request->input('category_id'));
            })
            ->where('is_active', true)
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
     * Low stock items.
     */
    public function lowStock(): JsonResponse
    {
        $items = $this->stockService->lowStockItems();

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    /**
     * Near expiry stock.
     */
    public function nearExpiry(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);

        $batches = $this->stockService->nearExpiryBatches($days);

        return response()->json([
            'success' => true,
            'data'    => $batches,
            'meta'    => ['days_filter' => $days],
        ]);
    }

    /**
     * Expired stock with remaining quantity.
     */
    public function expired(): JsonResponse
    {
        $batches = $this->stockService->expiredBatches();

        return response()->json([
            'success' => true,
            'data'    => $batches,
        ]);
    }

    /**
     * Dead stock (no movement in X days).
     */
    public function deadStock(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 90);

        $items = $this->stockService->deadStockItems($days);

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => ['days_filter' => $days],
        ]);
    }

    /**
     * Fast/slow moving items analysis.
     */
    public function movementAnalysis(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);

        $analysis = $this->stockService->movementAnalysis($days);

        return response()->json([
            'success' => true,
            'data'    => $analysis,
            'meta'    => ['days_filter' => $days],
        ]);
    }
}
