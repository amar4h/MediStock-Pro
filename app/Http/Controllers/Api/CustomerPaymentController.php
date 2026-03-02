<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPaymentController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledgerService,
    ) {}

    /**
     * List payment history for a customer.
     */
    public function index(Customer $customer): JsonResponse
    {
        $payments = $customer->customerPayments()
            ->with(['sale:id,invoice_number', 'createdBy:id,name'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $payments->items(),
            'meta'    => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'per_page'     => $payments->perPage(),
                'total'        => $payments->total(),
            ],
        ]);
    }

    /**
     * Record a payment from a customer.
     */
    public function store(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'payment_mode' => 'required|in:cash,upi,bank_transfer,cheque',
            'payment_date' => 'required|date',
            'sale_id'      => 'nullable|exists:sales,id',
            'reference_no' => 'nullable|string|max:100',
            'notes'        => 'nullable|string',
        ]);

        $payment = $customer->customerPayments()->create(array_merge(
            $validated,
            ['created_by' => auth()->id()]
        ));

        // If linked to a specific sale, update that sale's balance
        if ($payment->sale_id) {
            $sale = $payment->sale;
            $sale->update([
                'paid_amount'    => $sale->paid_amount + $payment->amount,
                'balance_amount' => max(0, $sale->balance_amount - $payment->amount),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data'    => $payment->load(['customer:id,name', 'createdBy:id,name']),
        ], 201);
    }

    /**
     * Get a specific payment.
     */
    public function show(Customer $customer, CustomerPayment $payment): JsonResponse
    {
        // Ensure payment belongs to this customer
        if ($payment->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found for this customer.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $payment->load(['sale:id,invoice_number', 'createdBy:id,name']),
        ]);
    }
}
