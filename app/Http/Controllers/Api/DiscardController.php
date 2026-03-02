<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockDiscardRequest;
use App\Models\StockDiscard;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscardController extends Controller
{
    public function __construct(
        private readonly StockService $stockService,
    ) {}

    /**
     * List all stock discards.
     */
    public function index(Request $request): JsonResponse
    {
        $discards = StockDiscard::with([
            'item:id,name,unit',
            'batch:id,batch_number,expiry_date',
            'createdBy:id,name',
        ])
            ->when($request->filled('reason'), function ($q) use ($request) {
                $q->where('reason', $request->input('reason'));
            })
            ->when($request->filled('from'), function ($q) use ($request) {
                $q->where('discard_date', '>=', $request->input('from'));
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $q->where('discard_date', '<=', $request->input('to'));
            })
            ->orderByDesc('discard_date')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $discards->items(),
            'meta'    => [
                'current_page' => $discards->currentPage(),
                'last_page'    => $discards->lastPage(),
                'per_page'     => $discards->perPage(),
                'total'        => $discards->total(),
            ],
        ]);
    }

    /**
     * Discard stock (expired, damaged, lost, etc.).
     */
    public function store(StoreStockDiscardRequest $request): JsonResponse
    {
        $discard = $this->stockService->discardStock($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Stock discarded successfully.',
            'data'    => $discard->load([
                'item:id,name,unit',
                'batch:id,batch_number,expiry_date,stock_quantity',
                'createdBy:id,name',
            ]),
        ], 201);
    }

    /**
     * Get a specific discard record.
     */
    public function show(StockDiscard $discard): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $discard->load([
                'item:id,name,unit',
                'batch:id,batch_number,expiry_date',
                'createdBy:id,name',
            ]),
        ]);
    }
}
