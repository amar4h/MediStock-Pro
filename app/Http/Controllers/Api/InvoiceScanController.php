<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceScanRequest;
use App\Http\Resources\InvoiceScanResource;
use App\Models\InvoiceScan;
use App\Services\InvoiceScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceScanController extends Controller
{
    public function __construct(
        private readonly InvoiceScanService $invoiceScanService,
    ) {}

    /**
     * List scan history (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $scans = InvoiceScan::with(['user:id,name', 'purchase:id,invoice_number'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => InvoiceScanResource::collection($scans),
            'meta'    => [
                'current_page' => $scans->currentPage(),
                'last_page'    => $scans->lastPage(),
                'per_page'     => $scans->perPage(),
                'total'        => $scans->total(),
            ],
        ]);
    }

    /**
     * Upload and initiate invoice scan (OCR + parse).
     */
    public function store(StoreInvoiceScanRequest $request): JsonResponse
    {
        $scan = $this->invoiceScanService->uploadAndScan(
            $request->file('image'),
            auth()->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Invoice scan initiated successfully.',
            'data'    => new InvoiceScanResource($scan),
        ], 201);
    }

    /**
     * Get scan details and result.
     */
    public function show(InvoiceScan $invoiceScan): JsonResponse
    {
        $invoiceScan->load(['user:id,name', 'purchase:id,invoice_number']);

        return response()->json([
            'success' => true,
            'data'    => new InvoiceScanResource($invoiceScan),
        ]);
    }

    /**
     * Update a scan record (e.g., correct extracted data before confirming).
     */
    public function update(Request $request, InvoiceScan $invoiceScan): JsonResponse
    {
        $validated = $request->validate([
            'extracted_data' => 'nullable|array',
        ]);

        $invoiceScan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Invoice scan updated successfully.',
            'data'    => new InvoiceScanResource($invoiceScan->fresh()),
        ]);
    }

    /**
     * Soft delete a scan record.
     */
    public function destroy(InvoiceScan $invoiceScan): JsonResponse
    {
        $invoiceScan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice scan deleted successfully.',
        ]);
    }

    /**
     * Process (re-process) a scan — trigger OCR + parsing again.
     */
    public function process(InvoiceScan $invoiceScan): JsonResponse
    {
        $scan = $this->invoiceScanService->processScan($invoiceScan);

        return response()->json([
            'success' => true,
            'message' => 'Invoice scan re-processed successfully.',
            'data'    => new InvoiceScanResource($scan),
        ]);
    }

    /**
     * Confirm extracted data and create a purchase from the scan.
     */
    public function confirm(Request $request, InvoiceScan $invoiceScan): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|max:100',
            'invoice_date'  => 'required|date',
            'items'         => 'required|array|min:1',
            'payment_mode'  => 'required|in:cash,credit,partial',
            'paid_amount'   => 'nullable|numeric|min:0',
        ]);

        $purchase = $this->invoiceScanService->confirmAndCreatePurchase(
            $invoiceScan,
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Purchase created from invoice scan.',
            'data'    => [
                'scan'     => new InvoiceScanResource($invoiceScan->fresh()),
                'purchase' => $purchase->load(['supplier:id,name', 'purchaseItems.item:id,name']),
            ],
        ]);
    }

    /**
     * Get the parsed/extracted result from a scan.
     */
    public function result(InvoiceScan $invoiceScan): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'status'         => $invoiceScan->status,
                'confidence'     => $invoiceScan->ocr_confidence,
                'extracted_data' => $invoiceScan->extracted_data,
                'warnings'       => $invoiceScan->warnings,
                'error_message'  => $invoiceScan->error_message,
                'processing_ms'  => $invoiceScan->processing_ms,
            ],
        ]);
    }
}
