<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService,
    ) {}

    /**
     * List sales (paginated, filterable by date, customer, status).
     */
    public function index(Request $request): JsonResponse
    {
        $sales = Sale::with(['customer:id,name,phone', 'createdBy:id,name'])
            ->withCount('saleItems')
            ->when($request->filled('customer_id'), function ($q) use ($request) {
                $q->where('customer_id', $request->input('customer_id'));
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
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->input('search');
                $q->where(function ($query) use ($term) {
                    $query->where('invoice_number', 'like', "%{$term}%")
                          ->orWhere('patient_name', 'like', "%{$term}%")
                          ->orWhere('doctor_name', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => SaleResource::collection($sales),
            'meta'    => [
                'current_page' => $sales->currentPage(),
                'last_page'    => $sales->lastPage(),
                'per_page'     => $sales->perPage(),
                'total'        => $sales->total(),
            ],
        ]);
    }

    /**
     * Create a sale via SaleService (FIFO batch selection, stock deduction).
     */
    public function store(StoreSaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->createSale($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Sale created successfully.',
            'data'    => new SaleResource(
                $sale->load([
                    'customer:id,name,phone',
                    'saleItems.item:id,name,unit',
                    'saleItems.batch:id,batch_number,expiry_date',
                    'createdBy:id,name',
                ])
            ),
        ], 201);
    }

    /**
     * Get sale details with items.
     */
    public function show(int $id): JsonResponse
    {
        $sale = Sale::with([
            'customer',
            'saleItems.item:id,name,unit,composition',
            'saleItems.batch:id,batch_number,expiry_date,mrp',
            'saleReturns.saleReturnItems',
            'createdBy:id,name',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new SaleResource($sale),
        ]);
    }

    /**
     * Update a sale (limited to metadata fields).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $sale = Sale::findOrFail($id);

        $validated = $request->validate([
            'doctor_name'  => 'nullable|string|max:255',
            'patient_name' => 'nullable|string|max:255',
            'notes'        => 'nullable|string',
        ]);

        $sale->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sale updated successfully.',
            'data'    => new SaleResource($sale->fresh()->load(['customer:id,name', 'createdBy:id,name'])),
        ]);
    }

    /**
     * Delete a sale (soft delete, only if no returns).
     */
    public function destroy(int $id): JsonResponse
    {
        $sale = Sale::findOrFail($id);

        if ($sale->saleReturns()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete sale with existing returns.',
            ], 422);
        }

        $this->saleService->deleteSale($sale);

        return response()->json([
            'success' => true,
            'message' => 'Sale deleted successfully.',
        ]);
    }
}
