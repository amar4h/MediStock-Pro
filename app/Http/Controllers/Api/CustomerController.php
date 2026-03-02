<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledgerService,
    ) {}

    /**
     * List customers (paginated, searchable).
     */
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->input('search');
                $q->where(function ($query) use ($term) {
                    $query->where('name', 'like', "%{$term}%")
                          ->orWhere('phone', 'like', "%{$term}%")
                          ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('name')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => CustomerResource::collection($customers),
            'meta'    => [
                'current_page' => $customers->currentPage(),
                'last_page'    => $customers->lastPage(),
                'per_page'     => $customers->perPage(),
                'total'        => $customers->total(),
            ],
        ]);
    }

    /**
     * Create a new customer.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully.',
            'data'    => new CustomerResource($customer),
        ], 201);
    }

    /**
     * Get customer details with ledger summary.
     */
    public function show(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $ledgerSummary = $this->ledgerService->customerBalance($customer);

        return response()->json([
            'success' => true,
            'data'    => array_merge(
                (new CustomerResource($customer))->resolve(),
                ['ledger_summary' => $ledgerSummary]
            ),
        ]);
    }

    /**
     * Update a customer.
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully.',
            'data'    => new CustomerResource($customer->fresh()),
        ]);
    }

    /**
     * Soft delete a customer.
     */
    public function destroy(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        // Check for outstanding balance before deleting
        $balance = $this->ledgerService->customerBalance($customer);
        if (($balance['outstanding_balance'] ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete customer with outstanding balance.',
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully.',
        ]);
    }
}
