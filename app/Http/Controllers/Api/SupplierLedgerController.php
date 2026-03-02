<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierLedgerController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledgerService,
    ) {}

    /**
     * Get the supplier's full ledger (purchases + payments timeline).
     */
    public function index(Request $request, Supplier $supplier): JsonResponse
    {
        $from = $request->input('from');
        $to   = $request->input('to');

        $ledger = $this->ledgerService->supplierLedger($supplier, $from, $to);

        return response()->json([
            'success' => true,
            'data'    => $ledger,
        ]);
    }

    /**
     * Get the supplier's current balance summary.
     */
    public function balance(Supplier $supplier): JsonResponse
    {
        $balance = $this->ledgerService->supplierBalance($supplier);

        return response()->json([
            'success' => true,
            'data'    => $balance,
        ]);
    }
}
