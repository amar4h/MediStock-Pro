<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierPaymentController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledgerService,
    ) {}

    /**
     * List payment history for a supplier.
     */
    public function index(Supplier $supplier): JsonResponse
    {
        $payments = $supplier->supplierPayments()
            ->with(['purchase:id,invoice_number', 'createdBy:id,name'])
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
     * Record a payment to a supplier.
     */
    public function store(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'payment_mode' => 'required|in:cash,upi,bank_transfer,cheque',
            'payment_date' => 'required|date',
            'purchase_id'  => 'nullable|exists:purchases,id',
            'reference_no' => 'nullable|string|max:100',
            'notes'        => 'nullable|string',
        ]);

        $payment = $supplier->supplierPayments()->create(array_merge(
            $validated,
            ['created_by' => auth()->id()]
        ));

        // If linked to a specific purchase, update that purchase's balance
        if ($payment->purchase_id) {
            $purchase = $payment->purchase;
            $purchase->update([
                'paid_amount'    => $purchase->paid_amount + $payment->amount,
                'balance_amount' => max(0, $purchase->balance_amount - $payment->amount),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data'    => $payment->load(['supplier:id,name', 'createdBy:id,name']),
        ], 201);
    }

    /**
     * Get a specific payment.
     */
    public function show(Supplier $supplier, SupplierPayment $payment): JsonResponse
    {
        // Ensure payment belongs to this supplier
        if ($payment->supplier_id !== $supplier->id) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found for this supplier.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $payment->load(['purchase:id,invoice_number', 'createdBy:id,name']),
        ]);
    }
}
