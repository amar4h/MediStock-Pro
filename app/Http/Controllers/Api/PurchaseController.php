<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
    ) {}

    /**
     * List purchases (paginated, filterable by date and supplier).
     */
    public function index(Request $request): JsonResponse
    {
        $purchases = Purchase::with(['supplier:id,name', 'createdBy:id,name'])
            ->withCount('purchaseItems')
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                $q->where('supplier_id', $request->input('supplier_id'));
            })
            ->when($request->filled('from'), function ($q) use ($request) {
                $q->where('invoice_date', '>=', $request->input('from'));
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $q->where('invoice_date', '<=', $request->input('to'));
            })
            ->when($request->filled('payment_mode'), function ($q) use ($request) {
                $q->where('payment_mode', $request->input('payment_mode'));
            })
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => PurchaseResource::collection($purchases),
            'meta'    => [
                'current_page' => $purchases->currentPage(),
                'last_page'    => $purchases->lastPage(),
                'per_page'     => $purchases->perPage(),
                'total'        => $purchases->total(),
            ],
        ]);
    }

    /**
     * Create a purchase via PurchaseService (creates batches, stock movements).
     */
    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $purchase = $this->purchaseService->createPurchase($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Purchase created successfully.',
            'data'    => new PurchaseResource(
                $purchase->load(['supplier:id,name', 'purchaseItems.item:id,name', 'createdBy:id,name'])
            ),
        ], 201);
    }

    /**
     * Get purchase details with items.
     */
    public function show(int $id): JsonResponse
    {
        $purchase = Purchase::with([
            'supplier',
            'purchaseItems.item:id,name,unit',
            'purchaseItems.batch',
            'purchaseReturns.purchaseReturnItems',
            'createdBy:id,name',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new PurchaseResource($purchase),
        ]);
    }

    /**
     * Update a purchase (only if it has outstanding balance / is editable).
     */
    public function update(UpdatePurchaseRequest $request, int $id): JsonResponse
    {
        $purchase = Purchase::findOrFail($id);

        // Only allow update if purchase has not been fully paid or is still recent
        if ($purchase->payment_mode === 'cash' && $purchase->balance_amount == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update a fully paid cash purchase.',
            ], 422);
        }

        $purchase = $this->purchaseService->updatePurchase($purchase, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Purchase updated successfully.',
            'data'    => new PurchaseResource(
                $purchase->load(['supplier:id,name', 'purchaseItems.item:id,name', 'createdBy:id,name'])
            ),
        ]);
    }

    /**
     * Delete a purchase.
     */
    public function destroy(int $id): JsonResponse
    {
        $purchase = Purchase::findOrFail($id);

        // Only allow deletion if no returns have been made against this purchase
        if ($purchase->purchaseReturns()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete purchase with existing returns.',
            ], 422);
        }

        $this->purchaseService->deletePurchase($purchase);

        return response()->json([
            'success' => true,
            'message' => 'Purchase deleted successfully.',
        ]);
    }
}
