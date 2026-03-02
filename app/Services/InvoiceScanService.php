<?php

namespace App\Services;

use App\Exceptions\InvoiceScanException;
use App\Models\InvoiceScan;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceParser\InvoiceParserInterface;
use App\Services\InvoiceParser\ParsedInvoiceDTO;
use App\Services\OCR\OCRException;
use App\Services\OCR\OCRServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Orchestrator service for the invoice scanning workflow.
 *
 * Flow: Upload image -> OCR -> Parse -> Match supplier -> Match items -> Save results
 */
class InvoiceScanService
{
    public function __construct(
        private readonly OCRServiceInterface $ocrService,
        private readonly InvoiceParserInterface $invoiceParser,
        private readonly ItemMatchingService $itemMatcher,
    ) {}

    /**
     * Scan and parse an uploaded invoice image.
     *
     * Complete workflow:
     * 1. Store image to private storage
     * 2. Create InvoiceScan record (status: processing)
     * 3. Call OCR service to extract text
     * 4. Call parser to extract structured data
     * 5. Match supplier (GSTIN exact match, then name fuzzy)
     * 6. Match items (exact -> FULLTEXT -> LIKE + Levenshtein)
     * 7. Build extracted_data JSON
     * 8. Update scan record with results
     * 9. Handle errors gracefully
     *
     * @param  UploadedFile  $image  The uploaded invoice image
     * @param  User          $user   The authenticated user
     * @return InvoiceScan
     */
    public function scanAndParse(UploadedFile $image, User $user): InvoiceScan
    {
        $startTime = microtime(true);
        $tenantId = $user->tenant_id;

        // 1. Store image to private storage
        $imagePath = $image->store(
            "tenants/{$tenantId}/invoice-scans",
            'local'
        );

        // 2. Create InvoiceScan record
        $scan = InvoiceScan::create([
            'tenant_id'  => $tenantId,
            'user_id'    => $user->id,
            'image_path' => $imagePath,
            'status'     => 'processing',
        ]);

        $warnings = [];

        try {
            // 3. Call OCR service
            $imageContent = Storage::disk('local')->get($imagePath);
            $ocrResult = $this->ocrService->detectDocumentText($imageContent);

            if (! $ocrResult->hasText()) {
                $this->markFailed($scan, 'OCR returned no text from the image.', $startTime);
                return $scan->fresh();
            }

            $scan->update([
                'raw_ocr_text'   => $ocrResult->fullText,
                'ocr_confidence' => $ocrResult->confidence,
            ]);

            if (! $ocrResult->isHighConfidence()) {
                $warnings[] = 'OCR confidence is low (' . round($ocrResult->confidence * 100, 1) . '%). Results may be inaccurate.';
            }

            // 4. Call parser to extract structured data
            $parsedInvoice = $this->invoiceParser->parse($ocrResult->fullText);

            // 5. Match supplier
            $supplierMatch = $this->matchSupplier($parsedInvoice, $tenantId);
            if ($supplierMatch === null && $parsedInvoice->supplierName) {
                $warnings[] = "Supplier \"{$parsedInvoice->supplierName}\" not found in your records.";
            }

            // 6. Match items
            $matchedItems = [];
            foreach ($parsedInvoice->items as $index => $parsedItem) {
                $itemMatch = null;
                if ($parsedItem->itemName) {
                    $itemMatch = $this->itemMatcher->findBestMatch($parsedItem->itemName, $tenantId);
                }

                $matchedItems[] = [
                    'parsed'  => $parsedItem->toArray(),
                    'match'   => $itemMatch,
                ];

                if ($itemMatch === null && $parsedItem->itemName) {
                    $warnings[] = "Item \"{$parsedItem->itemName}\" (row {$index}) could not be matched.";
                } elseif ($itemMatch && $itemMatch['match_confidence'] === 'low') {
                    $warnings[] = "Item \"{$parsedItem->itemName}\" (row {$index}) has low match confidence for \"{$itemMatch['matched_name']}\".";
                }
            }

            // 7. Build extracted_data
            $extractedData = [
                'supplier'       => [
                    'name'          => $parsedInvoice->supplierName,
                    'gstin'         => $parsedInvoice->supplierGstin,
                    'drug_license'  => $parsedInvoice->supplierDrugLicense,
                    'matched_id'    => $supplierMatch?->id,
                    'matched_name'  => $supplierMatch?->name,
                ],
                'invoice_number' => $parsedInvoice->invoiceNumber,
                'invoice_date'   => $parsedInvoice->invoiceDate,
                'items'          => $matchedItems,
                'totals'         => [
                    'subtotal'        => $parsedInvoice->subtotal,
                    'gst_amount'      => $parsedInvoice->gstAmount,
                    'discount_amount' => $parsedInvoice->discountAmount,
                    'total_amount'    => $parsedInvoice->totalAmount,
                ],
                'metadata'       => $parsedInvoice->metadata,
            ];

            // 8. Determine final status
            $status = 'completed';
            $unmatchedItemCount = collect($matchedItems)->filter(fn($m) => $m['match'] === null)->count();

            if ($unmatchedItemCount > 0 || $supplierMatch === null) {
                $status = 'partial';
            }

            if (empty($parsedInvoice->items)) {
                $status = 'partial';
                $warnings[] = 'No line items could be extracted from the invoice.';
            }

            $processingMs = (int) ((microtime(true) - $startTime) * 1000);

            $scan->update([
                'status'         => $status,
                'extracted_data' => $extractedData,
                'warnings'       => ! empty($warnings) ? $warnings : null,
                'processing_ms'  => $processingMs,
            ]);

            return $scan->fresh();

        } catch (OCRException $e) {
            Log::error('Invoice scan OCR failure', [
                'scan_id' => $scan->id,
                'error'   => $e->getMessage(),
            ]);
            $this->markFailed($scan, "OCR failed: {$e->getMessage()}", $startTime);
            return $scan->fresh();

        } catch (\App\Services\InvoiceParser\ParsingException $e) {
            Log::error('Invoice scan parsing failure', [
                'scan_id' => $scan->id,
                'error'   => $e->getMessage(),
            ]);
            $this->markFailed($scan, "Parsing failed: {$e->getMessage()}", $startTime);
            return $scan->fresh();

        } catch (\Throwable $e) {
            Log::error('Invoice scan unexpected error', [
                'scan_id' => $scan->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->markFailed($scan, "Unexpected error: {$e->getMessage()}", $startTime);
            return $scan->fresh();
        }
    }

    /**
     * Match a supplier using GSTIN exact match first, then name fuzzy match.
     */
    private function matchSupplier(ParsedInvoiceDTO $invoice, int $tenantId): ?Supplier
    {
        // Strategy 1: Exact GSTIN match
        if ($invoice->supplierGstin) {
            $supplier = Supplier::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('gstin', $invoice->supplierGstin)
                ->whereNull('deleted_at')
                ->first();

            if ($supplier) {
                return $supplier;
            }
        }

        // Strategy 2: Fuzzy name match
        if ($invoice->supplierName) {
            $searchName = trim($invoice->supplierName);

            // Exact name match (case-insensitive)
            $supplier = Supplier::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($searchName)])
                ->whereNull('deleted_at')
                ->first();

            if ($supplier) {
                return $supplier;
            }

            // LIKE match
            $supplier = Supplier::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('name', 'LIKE', "%{$searchName}%")
                ->whereNull('deleted_at')
                ->first();

            if ($supplier) {
                return $supplier;
            }

            // Try matching with significant keywords
            $words = preg_split('/[\s\-&]+/', $searchName);
            $significantWords = array_filter($words, fn($w) => mb_strlen(trim($w)) >= 3);

            if (! empty($significantWords)) {
                $query = Supplier::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at');

                foreach ($significantWords as $word) {
                    $query->where('name', 'LIKE', "%{$word}%");
                }

                $supplier = $query->first();
                if ($supplier) {
                    return $supplier;
                }
            }
        }

        return null;
    }

    /**
     * Mark a scan as failed with an error message.
     */
    private function markFailed(InvoiceScan $scan, string $errorMessage, float $startTime): void
    {
        $processingMs = (int) ((microtime(true) - $startTime) * 1000);

        $scan->update([
            'status'        => 'failed',
            'error_message' => $errorMessage,
            'processing_ms' => $processingMs,
        ]);
    }
}
