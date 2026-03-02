<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    /**
     * List all stock movements (paginated, filterable).
     */
    public function index(Request $request): JsonResponse
    {
        $movements = StockMovement::with([
            'item:id,name,unit',
            'batch:id,batch_number,expiry_date',
            'createdBy:id,name',
        ])
            ->when($request->filled('item_id'), function ($q) use ($request) {
                $q->where('item_id', $request->input('item_id'));
            })
            ->when($request->filled('movement_type'), function ($q) use ($request) {
                $q->where('movement_type', $request->input('movement_type'));
            })
            ->when($request->filled('from'), function ($q) use ($request) {
                $q->where('created_at', '>=', $request->input('from'));
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $q->where('created_at', '<=', $request->input('to') . ' 23:59:59');
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $movements->items(),
            'meta'    => [
                'current_page' => $movements->currentPage(),
                'last_page'    => $movements->lastPage(),
                'per_page'     => $movements->perPage(),
                'total'        => $movements->total(),
            ],
        ]);
    }

    /**
     * Get a specific stock movement.
     */
    public function show(StockMovement $movement): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $movement->load([
                'item:id,name,unit',
                'batch:id,batch_number,expiry_date',
                'createdBy:id,name',
            ]),
        ]);
    }
}
