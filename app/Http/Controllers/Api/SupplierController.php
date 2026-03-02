<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledgerService,
    ) {}

    /**
     * List suppliers (paginated, searchable).
     */
    public function index(Request $request): JsonResponse
    {
        $suppliers = Supplier::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->input('search');
                $q->where(function ($query) use ($term) {
                    $query->where('name', 'like', "%{$term}%")
                          ->orWhere('phone', 'like', "%{$term}%")
                          ->orWhere('gstin', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('name')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => SupplierResource::collection($suppliers),
            'meta'    => [
                'current_page' => $suppliers->currentPage(),
                'last_page'    => $suppliers->lastPage(),
                'per_page'     => $suppliers->perPage(),
                'total'        => $suppliers->total(),
            ],
        ]);
    }

    /**
     * Create a new supplier.
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Supplier created successfully.',
            'data'    => new SupplierResource($supplier),
        ], 201);
    }

    /**
     * Get supplier details with ledger summary.
     */
    public function show(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $ledgerSummary = $this->ledgerService->supplierBalance($supplier);

        return response()->json([
            'success' => true,
            'data'    => array_merge(
                (new SupplierResource($supplier))->resolve(),
                ['ledger_summary' => $ledgerSummary]
            ),
        ]);
    }

    /**
     * Update a supplier.
     */
    public function update(UpdateSupplierRequest $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully.',
            'data'    => new SupplierResource($supplier->fresh()),
        ]);
    }

    /**
     * Soft delete a supplier.
     */
    public function destroy(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        // Check for outstanding balance before deleting
        $balance = $this->ledgerService->supplierBalance($supplier);
        if (($balance['outstanding_balance'] ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete supplier with outstanding balance.',
            ], 422);
        }

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully.',
        ]);
    }
}
