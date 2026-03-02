<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerLedgerController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledgerService,
    ) {}

    /**
     * Get the customer's full ledger (sales + payments timeline).
     */
    public function index(Request $request, Customer $customer): JsonResponse
    {
        $from = $request->input('from');
        $to   = $request->input('to');

        $ledger = $this->ledgerService->customerLedger($customer, $from, $to);

        return response()->json([
            'success' => true,
            'data'    => $ledger,
        ]);
    }

    /**
     * Get the customer's current balance summary.
     */
    public function balance(Customer $customer): JsonResponse
    {
        $balance = $this->ledgerService->customerBalance($customer);

        return response()->json([
            'success' => true,
            'data'    => $balance,
        ]);
    }
}
