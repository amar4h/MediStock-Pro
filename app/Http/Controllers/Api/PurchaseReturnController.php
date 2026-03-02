<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseReturnRequest;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
    ) {}

    /**
     * Create a purchase return for a specific purchase.
     */
    public function store(StorePurchaseReturnRequest $request, Purchase $purchase): JsonResponse
    {
        $purchaseReturn = $this->purchaseService->createPurchaseReturn(
            $purchase,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Purchase return created successfully.',
            'data'    => $purchaseReturn->load([
                'purchase:id,invoice_number,supplier_id',
                'purchaseReturnItems.item:id,name',
                'purchaseReturnItems.batch:id,batch_number',
                'createdBy:id,name',
            ]),
        ], 201);
    }

    /**
     * List returns for a specific purchase.
     */
    public function index(Purchase $purchase): JsonResponse
    {
        $returns = $purchase->purchaseReturns()
            ->with([
                'purchaseReturnItems.item:id,name',
                'purchaseReturnItems.batch:id,batch_number',
                'createdBy:id,name',
            ])
            ->orderByDesc('return_date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $returns,
        ]);
    }

    /**
     * List all purchase returns across all purchases (tenant-scoped).
     */
    public function all(Request $request): JsonResponse
    {
        $returns = PurchaseReturn::with([
            'purchase:id,invoice_number,supplier_id',
            'purchase.supplier:id,name',
            'purchaseReturnItems.item:id,name',
            'createdBy:id,name',
        ])
            ->when($request->filled('from'), function ($q) use ($request) {
                $q->where('return_date', '>=', $request->input('from'));
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $q->where('return_date', '<=', $request->input('to'));
            })
            ->orderByDesc('return_date')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $returns->items(),
            'meta'    => [
                'current_page' => $returns->currentPage(),
                'last_page'    => $returns->lastPage(),
                'per_page'     => $returns->perPage(),
                'total'        => $returns->total(),
            ],
        ]);
    }
}
