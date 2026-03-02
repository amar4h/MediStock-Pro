<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BatchResource;
use App\Models\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    /**
     * List batches, optionally filtered by item_id.
     */
    public function index(Request $request): JsonResponse
    {
        $batches = Batch::with('item:id,name,unit')
            ->when($request->filled('item_id'), function ($q) use ($request) {
                $q->where('item_id', $request->input('item_id'));
            })
            ->when($request->filled('has_stock'), function ($q) {
                $q->where('stock_quantity', '>', 0);
            })
            ->orderBy('expiry_date')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => BatchResource::collection($batches),
            'meta'    => [
                'current_page' => $batches->currentPage(),
                'last_page'    => $batches->lastPage(),
                'per_page'     => $batches->perPage(),
                'total'        => $batches->total(),
            ],
        ]);
    }

    /**
     * Get a specific batch.
     */
    public function show(Batch $batch): JsonResponse
    {
        $batch->load('item:id,name,unit,composition');

        return response()->json([
            'success' => true,
            'data'    => new BatchResource($batch),
        ]);
    }

    /**
     * Near expiry batches with stock > 0.
     */
    public function nearExpiry(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);

        $batches = Batch::with('item:id,name,unit')
            ->nearExpiry($days)
            ->orderBy('expiry_date')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => BatchResource::collection($batches),
            'meta'    => [
                'current_page' => $batches->currentPage(),
                'last_page'    => $batches->lastPage(),
                'per_page'     => $batches->perPage(),
                'total'        => $batches->total(),
                'days_filter'  => $days,
            ],
        ]);
    }

    /**
     * Expired batches with stock > 0.
     */
    public function expired(): JsonResponse
    {
        $batches = Batch::with('item:id,name,unit')
            ->expired()
            ->where('stock_quantity', '>', 0)
            ->orderBy('expiry_date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => BatchResource::collection($batches),
        ]);
    }
}
