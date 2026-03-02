Now I have a thorough understanding of the architecture, database schema, API structure, Google Cloud Vision capabilities, and pricing. Let me compose the comprehensive design document.

---

# Invoice Scan to Purchase Entry -- Detailed Design Document

## Table of Contents

1. [Complete User Flow](#1-complete-user-flow)
2. [API Endpoints](#2-api-endpoints)
3. [Service Class Design](#3-service-class-design)
4. [Google Cloud Vision Integration](#4-google-cloud-vision-integration)
5. [Text Parsing Strategy](#5-text-parsing-strategy)
6. [Database Changes](#6-database-changes)
7. [Frontend UX Design](#7-frontend-ux-design)
8. [Error Handling and Edge Cases](#8-error-handling-and-edge-cases)
9. [Cost Estimation](#9-cost-estimation)
10. [LLM Parsing vs Regex Parsing -- Comparison and Recommendation](#10-llm-parsing-vs-regex-parsing)

---

## 1. Complete User Flow (Step by Step)

### Happy Path

```
Step 1: User navigates to Purchase Entry page
        - Sees the existing "New Purchase" form
        - NEW: A prominent "Scan Invoice" button appears at the top (camera icon)
        
Step 2: User taps "Scan Invoice"
        - On mobile: Native camera opens via <input type="file" accept="image/*" capture="environment">
        - On desktop: File picker opens for image upload
        - Supported formats: JPEG, PNG, WebP, PDF (single page)
        - Maximum file size: 5 MB (compressed on client if needed)
        
Step 3: Image preview + confirmation
        - Image thumbnail displayed in a modal/overlay
        - User sees "Processing..." button or can "Retake"
        - Client-side: Image is compressed to max 1600px width (Canvas API) to reduce upload size
        
Step 4: Upload + OCR Processing
        - Image uploaded via AJAX POST to /api/v1/invoice-scans
        - Server saves image to storage/app/private/invoice-scans/{tenant_id}/{uuid}.jpg
        - Server sends image to Google Cloud Vision API (DOCUMENT_TEXT_DETECTION)
        - Server receives raw OCR text
        - Server parses OCR text into structured data (via LLM or regex -- see Section 10)
        - Returns structured JSON to frontend
        - Total time: 3-8 seconds depending on image quality and network
        
Step 5: Pre-filled form with review
        - Purchase form fields auto-populated:
          * Supplier: Matched by name/GSTIN from extracted text against existing suppliers
          * Invoice Number: Extracted
          * Invoice Date: Extracted and parsed
          * Items table: Each line item pre-filled with:
            - Item name (fuzzy-matched against item master)
            - Batch number
            - Expiry date
            - MRP
            - Purchase price
            - Quantity
            - Free quantity (if detected)
            - GST %
            - Discount %
        - Fields with LOW CONFIDENCE are highlighted in yellow/amber
        - Fields that could NOT be extracted are left blank (red border)
        - Unmatched items (not in item master) show a "Create New Item" inline option
        
Step 6: User reviews and corrects
        - User edits any incorrect values
        - User selects/confirms supplier from dropdown if auto-match was wrong
        - User maps unrecognized items to existing items or creates new ones
        - User can add/remove line items manually
        - User fills in any missing fields
        
Step 7: Normal purchase submission
        - User clicks "Save Purchase" (same button as manual entry)
        - Validation runs through existing StorePurchaseRequest
        - PurchaseService->createPurchase() handles batch creation, stock movements, etc.
        - Everything downstream is IDENTICAL to manual purchase entry
```

### Alternative / Edge Paths

```
- Blurry image: OCR returns low-confidence text -> most fields left blank, 
  user prompted "Image quality too low, please retake or enter manually"
  
- Partial extraction: Some fields extracted, others not -> form partially 
  filled, blank fields have red indicators
  
- Duplicate invoice: After extraction, if invoice_number + supplier combo 
  already exists, show warning: "Invoice XYZ from Supplier ABC already exists"
  
- Multi-page invoice: For now, only first page supported. User prompted to 
  photograph each page separately or enter remaining items manually. 
  (Future: PDF multi-page support via asyncBatchAnnotate)
```

---

## 2. API Endpoints

These endpoints are added under the existing `/api/v1/` prefix, following the established convention.

### New Endpoints

```
POST   /api/v1/invoice-scans              Upload invoice image + trigger OCR + parse
GET    /api/v1/invoice-scans/{id}          Get scan result (for polling if async)
GET    /api/v1/invoice-scans               List scan history (paginated)
DELETE /api/v1/invoice-scans/{id}          Delete a scan record + image
```

### Endpoint Details

#### `POST /api/v1/invoice-scans`

**Purpose:** Upload an invoice image, run OCR, parse to structured data, return pre-filled purchase form data.

**Request:**
```
Content-Type: multipart/form-data

Fields:
  image: (file, required) JPEG/PNG/WebP/PDF, max 5MB
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "scan_id": 42,
    "raw_text": "Full OCR text for debugging...",
    "confidence_score": 0.85,
    "extracted": {
      "supplier": {
        "name": "ABC Distributors",
        "gstin": "27AABCU9603R1ZM",
        "drug_license_no": "MH/20B/12345",
        "matched_supplier_id": 7,
        "match_confidence": "high"
      },
      "invoice_number": "INV-2026-001234",
      "invoice_date": "2026-02-28",
      "items": [
        {
          "row_index": 1,
          "item_name": "Paracetamol 500mg Tab",
          "matched_item_id": 143,
          "match_confidence": "high",
          "batch_number": "BT2026A",
          "expiry_date": "2027-06",
          "mrp": 45.50,
          "purchase_price": 32.10,
          "quantity": 100,
          "free_quantity": 10,
          "gst_percent": 12.00,
          "discount_percent": 10.00,
          "hsn_code": "30049099",
          "pack_size": "10x10",
          "field_confidence": {
            "item_name": "high",
            "batch_number": "high",
            "expiry_date": "medium",
            "mrp": "high",
            "purchase_price": "high",
            "quantity": "high"
          }
        }
      ],
      "totals": {
        "subtotal": 32100.00,
        "gst_amount": 3852.00,
        "discount_amount": 3210.00,
        "total_amount": 32742.00
      }
    },
    "image_url": "/storage/invoice-scans/tenant_1/abc123.jpg",
    "warnings": [
      "Expiry date for row 3 could not be determined",
      "Item 'Amoxycillin 250mg' not found in item master"
    ]
  },
  "message": "Invoice scanned and parsed successfully"
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Could not extract text from image. Please ensure the image is clear and well-lit.",
  "errors": {
    "image": ["No text detected in uploaded image"]
  }
}
```

#### `GET /api/v1/invoice-scans/{id}`

Returns the scan record with its parsed data. Useful if the user navigates away and comes back, or for audit/history purposes.

#### `GET /api/v1/invoice-scans`

Paginated list of past scans for the current tenant. Filterable by date range. Useful for troubleshooting and audit trail.

#### `DELETE /api/v1/invoice-scans/{id}`

Soft-deletes the scan record. The image file is retained for 30 days (configurable), then purged by a scheduled command.

### Routing (added to `routes/api.php`)

```php
// Inside Route::middleware(['auth:sanctum', 'tenant.scope', 'tenant.active'])->prefix('v1')->group(...)

Route::prefix('invoice-scans')->group(function () {
    Route::get('/',      [InvoiceScanController::class, 'index']);
    Route::post('/',     [InvoiceScanController::class, 'store']);
    Route::get('/{id}',  [InvoiceScanController::class, 'show']);
    Route::delete('/{id}', [InvoiceScanController::class, 'destroy']);
});
```

### Permissions

A new permission `purchases.scan_invoice` is added, granted to roles: Owner, Store Manager, Pharmacist.

---

## 3. Service Class Design

### New Files to Create

```
app/
  Http/
    Controllers/
      Api/
        V1/
          InvoiceScanController.php         # Thin controller
    Requests/
      StoreInvoiceScanRequest.php           # Validation
    Resources/
      InvoiceScanResource.php               # API transformer
  Services/
    InvoiceScanService.php                  # Orchestrator
    OCR/
      GoogleVisionOCRService.php            # Google Cloud Vision wrapper
      OCRServiceInterface.php               # Interface (for swapping providers)
    InvoiceParser/
      InvoiceParserInterface.php            # Interface
      LLMInvoiceParser.php                  # Claude Haiku-based parser (RECOMMENDED)
      RegexInvoiceParser.php                # Regex fallback parser
      ParsedInvoiceDTO.php                  # Data Transfer Object
      ParsedInvoiceItemDTO.php              # Item-level DTO
  Models/
    InvoiceScan.php                         # Eloquent model
```

### Class Diagram and Responsibilities

#### `InvoiceScanController`

```php
class InvoiceScanController extends Controller
{
    public function __construct(
        private InvoiceScanService $scanService
    ) {}

    // POST /api/v1/invoice-scans
    public function store(StoreInvoiceScanRequest $request): JsonResponse
    {
        $scan = $this->scanService->scanAndParse(
            $request->file('image'),
            auth()->user()
        );
        return response()->json([
            'success' => true,
            'data' => new InvoiceScanResource($scan),
            'message' => 'Invoice scanned and parsed successfully',
        ]);
    }

    // GET /api/v1/invoice-scans
    public function index(Request $request): JsonResponse { /* paginated list */ }

    // GET /api/v1/invoice-scans/{id}
    public function show(int $id): JsonResponse { /* single record */ }

    // DELETE /api/v1/invoice-scans/{id}
    public function destroy(int $id): JsonResponse { /* soft delete */ }
}
```

#### `StoreInvoiceScanRequest`

```php
class StoreInvoiceScanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,webp,pdf',
                'max:5120', // 5 MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'image.max' => 'Invoice image must be under 5 MB. Try taking the photo in a well-lit area.',
            'image.mimes' => 'Only JPEG, PNG, WebP, and PDF files are supported.',
        ];
    }
}
```

#### `InvoiceScanService` (The Orchestrator)

This is the core service that ties everything together. It follows the existing pattern from `PurchaseService` and `SaleService` -- services handle business logic, controllers stay thin.

```php
class InvoiceScanService
{
    public function __construct(
        private OCRServiceInterface $ocrService,
        private InvoiceParserInterface $invoiceParser,
        private SupplierMatchingService $supplierMatcher,  // optional sub-service
        private ItemMatchingService $itemMatcher,          // optional sub-service
    ) {}

    /**
     * Main entry point: upload image -> OCR -> parse -> match -> return structured data
     */
    public function scanAndParse(UploadedFile $image, User $user): InvoiceScan
    {
        // 1. Store the image
        $path = $this->storeImage($image, $user->tenant_id);

        // 2. Create the InvoiceScan record (status: processing)
        $scan = InvoiceScan::create([
            'tenant_id'   => $user->tenant_id,
            'user_id'     => $user->id,
            'image_path'  => $path,
            'status'      => 'processing',
        ]);

        try {
            // 3. Send to Google Cloud Vision OCR
            $imageBinary = Storage::get($path);
            $ocrResult = $this->ocrService->detectDocumentText($imageBinary);

            // 4. Store raw OCR text
            $scan->update([
                'raw_ocr_text' => $ocrResult->fullText,
                'ocr_confidence' => $ocrResult->confidence,
            ]);

            // 5. Parse OCR text into structured invoice data
            $parsedInvoice = $this->invoiceParser->parse($ocrResult->fullText);

            // 6. Match supplier against existing suppliers in tenant
            $supplierMatch = $this->matchSupplier($parsedInvoice, $user->tenant_id);

            // 7. Match items against existing item master in tenant
            $itemMatches = $this->matchItems($parsedInvoice->items, $user->tenant_id);

            // 8. Build the final extracted_data JSON
            $extractedData = $this->buildExtractedData(
                $parsedInvoice, $supplierMatch, $itemMatches
            );

            // 9. Update scan record
            $scan->update([
                'status'         => 'completed',
                'extracted_data' => $extractedData,
                'warnings'       => $this->collectWarnings($parsedInvoice, $itemMatches),
            ]);

        } catch (OCRException $e) {
            $scan->update([
                'status'       => 'failed',
                'error_message' => 'OCR failed: ' . $e->getMessage(),
            ]);
            throw new InvoiceScanException('Could not extract text from image.', 422, $e);

        } catch (ParsingException $e) {
            $scan->update([
                'status'       => 'partial',
                'raw_ocr_text' => $ocrResult->fullText ?? null,
                'error_message' => 'Parsing failed: ' . $e->getMessage(),
            ]);
            // Still return partial data -- user can manually fill in the rest
        }

        return $scan->fresh();
    }

    private function storeImage(UploadedFile $image, int $tenantId): string
    {
        $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
        return $image->storeAs(
            "invoice-scans/{$tenantId}",
            $filename,
            'private'  // not publicly accessible
        );
    }

    /**
     * Fuzzy match supplier name/GSTIN against tenant's suppliers table
     */
    private function matchSupplier(ParsedInvoiceDTO $invoice, int $tenantId): ?array
    {
        // Strategy:
        // 1. Exact match on GSTIN (most reliable)
        // 2. Fuzzy match on supplier name using LIKE + Levenshtein
        
        if ($invoice->supplierGstin) {
            $supplier = Supplier::where('tenant_id', $tenantId)
                ->where('gstin', $invoice->supplierGstin)
                ->first();
            if ($supplier) {
                return [
                    'matched_supplier_id' => $supplier->id,
                    'match_confidence' => 'high',
                    'matched_by' => 'gstin',
                ];
            }
        }

        if ($invoice->supplierName) {
            // Fuzzy search using LIKE for partial match
            $candidates = Supplier::where('tenant_id', $tenantId)
                ->where('name', 'LIKE', '%' . Str::substr($invoice->supplierName, 0, 10) . '%')
                ->get();

            if ($candidates->count() === 1) {
                return [
                    'matched_supplier_id' => $candidates->first()->id,
                    'match_confidence' => 'medium',
                    'matched_by' => 'name_fuzzy',
                ];
            }
            // If multiple matches or none, return null -- user picks manually
        }

        return null;
    }

    /**
     * Fuzzy match each item name against tenant's items table
     */
    private function matchItems(array $parsedItems, int $tenantId): array
    {
        $matches = [];
        foreach ($parsedItems as $index => $parsedItem) {
            $match = $this->itemMatcher->findBestMatch(
                $parsedItem->itemName,
                $tenantId
            );
            $matches[$index] = $match;
        }
        return $matches;
    }
}
```

#### `OCRServiceInterface` and `GoogleVisionOCRService`

```php
interface OCRServiceInterface
{
    public function detectDocumentText(string $imageContent): OCRResult;
}

class OCRResult
{
    public function __construct(
        public readonly string $fullText,
        public readonly float $confidence,
        public readonly array $blocks,    // For spatial analysis if needed
    ) {}
}
```

```php
class GoogleVisionOCRService implements OCRServiceInterface
{
    /**
     * Uses Google Cloud Vision REST API directly (no gRPC dependency needed).
     * This is critical for Hostinger shared hosting where gRPC extension is NOT available.
     */
    public function detectDocumentText(string $imageContent): OCRResult
    {
        $base64Image = base64_encode($imageContent);

        $payload = [
            'requests' => [
                [
                    'image' => ['content' => $base64Image],
                    'features' => [
                        ['type' => 'DOCUMENT_TEXT_DETECTION'],
                    ],
                    'imageContext' => [
                        'languageHints' => ['en', 'hi'], // English + Hindi
                    ],
                ],
            ],
        ];

        $response = Http::timeout(30)
            ->post(
                'https://vision.googleapis.com/v1/images:annotate?key=' . config('services.google_vision.api_key'),
                $payload
            );

        if ($response->failed()) {
            throw new OCRException('Google Vision API request failed: ' . $response->status());
        }

        $result = $response->json();
        $annotation = $result['responses'][0]['fullTextAnnotation'] ?? null;

        if (!$annotation) {
            throw new OCRException('No text detected in image');
        }

        $confidence = $this->calculateAverageConfidence($annotation);
        $blocks = $this->extractBlocks($annotation);

        return new OCRResult(
            fullText: $annotation['text'],
            confidence: $confidence,
            blocks: $blocks,
        );
    }

    private function calculateAverageConfidence(array $annotation): float
    {
        $confidences = [];
        foreach ($annotation['pages'] ?? [] as $page) {
            foreach ($page['blocks'] ?? [] as $block) {
                if (isset($block['confidence'])) {
                    $confidences[] = $block['confidence'];
                }
            }
        }
        return count($confidences) > 0 ? array_sum($confidences) / count($confidences) : 0.0;
    }
}
```

**Important note about the Google Vision PHP client library:** The official `google/cloud-vision` Composer package depends on `ext-grpc`, which is NOT available on Hostinger shared hosting. Therefore, we use the **REST API directly** via Laravel's `Http` facade. This avoids any gRPC or native extension requirements. We only need a Google Cloud Vision API key (not a service account JSON file).

#### `InvoiceParserInterface` and DTOs

```php
interface InvoiceParserInterface
{
    public function parse(string $ocrText): ParsedInvoiceDTO;
}

class ParsedInvoiceDTO
{
    public function __construct(
        public readonly ?string $supplierName,
        public readonly ?string $supplierGstin,
        public readonly ?string $supplierDrugLicense,
        public readonly ?string $invoiceNumber,
        public readonly ?string $invoiceDate,      // Y-m-d or raw string
        public readonly array $items,              // ParsedInvoiceItemDTO[]
        public readonly ?float $subtotal,
        public readonly ?float $gstAmount,
        public readonly ?float $discountAmount,
        public readonly ?float $totalAmount,
        public readonly array $metadata,           // Any extra fields
    ) {}
}

class ParsedInvoiceItemDTO
{
    public function __construct(
        public readonly int $rowIndex,
        public readonly ?string $itemName,
        public readonly ?string $batchNumber,
        public readonly ?string $expiryDate,       // Raw string like "06/2027" or "JUN-27"
        public readonly ?float $mrp,
        public readonly ?float $purchasePrice,
        public readonly ?int $quantity,
        public readonly ?int $freeQuantity,
        public readonly ?float $gstPercent,
        public readonly ?float $discountPercent,
        public readonly ?string $hsnCode,
        public readonly ?string $packSize,
        public readonly array $fieldConfidence,    // ['item_name' => 'high', ...]
    ) {}
}
```

#### `ItemMatchingService`

A focused sub-service for fuzzy matching item names:

```php
class ItemMatchingService
{
    /**
     * Find the best matching item from the item master.
     * Strategy:
     * 1. Exact name match (case-insensitive)
     * 2. FULLTEXT search match (MySQL FULLTEXT index on items.name, items.composition)
     * 3. LIKE-based partial match
     * 4. No match found -> return null, user creates or selects manually
     */
    public function findBestMatch(string $extractedName, int $tenantId): ?array
    {
        // Normalize: remove extra spaces, lowercase
        $normalized = Str::squish(Str::lower($extractedName));

        // 1. Exact match
        $exact = Item::where('tenant_id', $tenantId)
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->first();

        if ($exact) {
            return [
                'matched_item_id' => $exact->id,
                'match_confidence' => 'high',
                'matched_name' => $exact->name,
            ];
        }

        // 2. FULLTEXT search
        $fulltextResults = Item::where('tenant_id', $tenantId)
            ->whereRaw(
                'MATCH(name, composition) AGAINST(? IN BOOLEAN MODE)',
                [$this->buildFulltextQuery($normalized)]
            )
            ->limit(5)
            ->get();

        if ($fulltextResults->count() === 1) {
            return [
                'matched_item_id' => $fulltextResults->first()->id,
                'match_confidence' => 'medium',
                'matched_name' => $fulltextResults->first()->name,
            ];
        }

        // 3. LIKE fallback -- first few significant words
        $words = explode(' ', $normalized);
        $significantWords = array_filter($words, fn($w) => strlen($w) > 2);
        $firstWord = reset($significantWords) ?: $normalized;

        $likeResults = Item::where('tenant_id', $tenantId)
            ->where('name', 'LIKE', '%' . $firstWord . '%')
            ->limit(10)
            ->get();

        if ($likeResults->count() >= 1) {
            // Pick best via simple Levenshtein distance
            $best = $likeResults->sortBy(fn($item) =>
                levenshtein(Str::lower($item->name), $normalized)
            )->first();

            $distance = levenshtein(Str::lower($best->name), $normalized);
            $maxLen = max(strlen($best->name), strlen($normalized));
            $similarity = 1 - ($distance / $maxLen);

            if ($similarity > 0.6) {
                return [
                    'matched_item_id' => $best->id,
                    'match_confidence' => $similarity > 0.85 ? 'high' : 'medium',
                    'matched_name' => $best->name,
                ];
            }
        }

        // 4. No match
        return null;
    }

    private function buildFulltextQuery(string $text): string
    {
        $words = explode(' ', $text);
        return implode(' ', array_map(fn($w) => '+' . $w . '*', $words));
    }
}
```

---

## 4. Google Cloud Vision Integration Approach

### Why REST API Instead of PHP Client Library

| Approach | Pros | Cons |
|----------|------|------|
| **REST API (direct HTTP)** | No ext-grpc needed. Works on Hostinger shared hosting. Simple `Http::post()`. | Must handle authentication manually. |
| **google/cloud-vision package** | Type-safe PHP objects. Auto-retry. | Requires ext-grpc or ext-protobuf. Neither available on Hostinger shared hosting. |

**Decision: REST API via API key.** The Google Cloud Vision REST endpoint accepts an API key as a query parameter. No service account JSON needed (though we could use one for more security later).

### Setup Steps

1. **Google Cloud Console:** Create a project, enable "Cloud Vision API", create an API key, restrict it to Vision API only + your domain's server IP.

2. **Laravel config** (in `config/services.php`):
```php
'google_vision' => [
    'api_key' => env('GOOGLE_VISION_API_KEY'),
    'enabled' => env('GOOGLE_VISION_ENABLED', true),
    'max_image_size' => env('GOOGLE_VISION_MAX_IMAGE_SIZE', 5242880), // 5 MB
],
```

3. **`.env`:**
```
GOOGLE_VISION_API_KEY=AIzaSy...your-key
GOOGLE_VISION_ENABLED=true
```

### API Call Details

**Endpoint:** `POST https://vision.googleapis.com/v1/images:annotate?key={API_KEY}`

**Feature used:** `DOCUMENT_TEXT_DETECTION` (not `TEXT_DETECTION`)

Why `DOCUMENT_TEXT_DETECTION` over `TEXT_DETECTION`:
- `DOCUMENT_TEXT_DETECTION` is optimized for dense text and documents (invoices are dense documents)
- Returns `fullTextAnnotation` with page/block/paragraph/word hierarchy
- Better at maintaining reading order in tabular layouts
- Same pricing tier ($1.50 per 1000 units after free tier)

**Language hints:** `['en', 'hi']` -- Indian invoices are primarily in English but may contain Hindi text for company names or addresses.

### Response Structure Used

```
fullTextAnnotation
  text: "Full extracted text as one string with newlines"
  pages[]
    blocks[]
      blockType: "TEXT"
      confidence: 0.98
      paragraphs[]
        words[]
          symbols[]
            text: "P"
            confidence: 0.99
          boundingBox: { vertices: [{x, y}, ...] }
```

We primarily use `fullTextAnnotation.text` (the complete extracted text as a string). The hierarchical page/block/paragraph data is stored but only used as a fallback for spatial analysis if the primary parser struggles with table alignment.

### Image Pre-processing (Client-Side)

Before upload, the frontend performs:
1. Resize to max 1600px on the longest edge (via Canvas API) -- reduces upload time
2. Convert to JPEG at 85% quality (reduces file size)
3. Strip EXIF metadata (privacy)
4. Apply auto-rotation based on EXIF orientation

This is important because pharmacy staff will be taking photos in variable lighting conditions with mid-range phones.

---

## 5. Text Parsing Strategy for Indian Pharmacy Invoices

### Understanding the Invoice Structure

A typical Indian pharmacy wholesale/distributor invoice (computer-generated) has this layout:

```
+----------------------------------------------------------+
| SUPPLIER NAME (Large text, top)                          |
| Address Line 1, City, State, PIN                         |
| GSTIN: 27AABCU9603R1ZM   DL No: MH/20B/12345           |
|                                                          |
| INVOICE / TAX INVOICE                                    |
| Invoice No: INV-2026-001234    Date: 28-02-2026          |
| Party: [Customer/Buyer name and details]                 |
|                                                          |
| Sr | Item Name        | Pack | HSN  | Batch | Exp  | Qty| 
|    |                  |      |      |       |      | Free|
|----|------------------|------|------|-------|------|-----|
|  1 | Paracetamol 500  |10x10 |30049 |BT26A |06/27 | 100|
|    |                  |      |      |       |      |  10|
|----|------------------|------|------|-------|------|-----|
|  2 | Amoxicillin 250  |10x10 |30041 |AM26B |03/28 |  50|
|    |                  |      |      |       |      |   5|
|                                                          |
|    (continued columns on right side of same row):        |
|    | MRP   | Rate  | Disc% | Amt    | CGST% | SGST% |   |
|    | 45.50 | 32.10 | 10.0  | 3210.0 |  6.0  |  6.0  |   |
|                                                          |
| TOTALS:                                                  |
| Subtotal: 32,100.00  CGST: 1,926.00  SGST: 1,926.00    |
| Total: 35,952.00     In Words: Thirty Five Thousand...   |
+----------------------------------------------------------+
```

### Key Variations Across Indian Distributors

| Field | Common Labels Found | Variations |
|-------|--------------------|------------|
| Supplier Name | Header text (first 1-3 lines) | Sometimes in ALL CAPS, sometimes in logo |
| GSTIN | "GSTIN:", "GST No:", "GSTIN No." | 15-char alphanumeric, always starts with 2-digit state code |
| Drug License | "DL:", "D.L. No:", "Drug Lic." | Format varies by state |
| Invoice No | "Invoice No:", "Inv No:", "Bill No:" | Alphanumeric |
| Date | "Date:", "Inv. Date:", "Invoice Date:" | DD-MM-YYYY, DD/MM/YYYY, DD.MM.YYYY |
| Batch | "Batch:", "B.No:", "Batch No" | Column in item table |
| Expiry | "Exp:", "Exp Date:", "Expiry" | MM/YY, MM/YYYY, MON-YY |
| MRP | "MRP", "M.R.P." | Decimal number |
| Rate/Price | "Rate", "Price", "Pur Rate", "P.Rate" | The purchase price column |
| Quantity | "Qty", "Quantity", "QTY" | Integer |
| Free | "Free", "Fre", "Fr" | Integer, sometimes same column as Qty |
| GST | "GST%", "GST", "CGST", "SGST", "IGST" | Percentage, split or combined |
| Discount | "Disc%", "Dis%", "Discount" | Percentage |

### Two-Strategy Approach

We implement **two parsers** behind the `InvoiceParserInterface`:

**Primary (Recommended): LLM-Based Parser (`LLMInvoiceParser`)** -- see Section 10 for full comparison.

**Fallback: Regex-Based Parser (`RegexInvoiceParser`)**

### Regex-Based Parser Strategy (for reference / fallback)

The regex parser works in 4 phases:

**Phase 1: Header Extraction (Top of Invoice)**

```php
// Supplier Name: First 1-3 non-empty lines (heuristic)
// GSTIN: Pattern \d{2}[A-Z]{5}\d{4}[A-Z]{1}[A-Z\d]{1}[Z]{1}[A-Z\d]{1}
// Drug License: Pattern [A-Z]{2}\/\d+[A-Z]?\/\d+
// Invoice Number: Look for "Invoice No", "Inv No", "Bill No" followed by : and value
// Invoice Date: Look for "Date" label, then parse DD-MM-YYYY / DD/MM/YYYY
```

```php
private function extractGstin(string $text): ?string
{
    // Indian GSTIN format: 2 digits + 5 uppercase + 4 digits + 1 uppercase + 1 alphanum + Z + 1 alphanum
    if (preg_match('/\b(\d{2}[A-Z]{5}\d{4}[A-Z][A-Z\d]Z[A-Z\d])\b/', $text, $match)) {
        return $match[1];
    }
    return null;
}

private function extractInvoiceNumber(string $text): ?string
{
    $patterns = [
        '/(?:Invoice\s*No|Inv\.?\s*No|Bill\s*No)[\s.:]*([A-Za-z0-9\-\/]+)/i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $match)) {
            return trim($match[1]);
        }
    }
    return null;
}

private function extractInvoiceDate(string $text): ?string
{
    // Look for date near "Date" label
    $patterns = [
        '/(?:Date|Inv\.?\s*Date|Invoice\s*Date)[\s.:]*(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})/i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $match)) {
            return $this->normalizeDate($match[1]);
        }
    }
    return null;
}
```

**Phase 2: Table Detection (Identify Item Rows)**

This is the hardest part of regex parsing. Strategy:

1. Split OCR text into lines
2. Identify the "header row" by looking for a line containing 3+ of: Item, Batch, Exp, MRP, Rate, Qty, HSN
3. Lines after the header row that start with a serial number (`^\s*\d+[\.\s]`) are item rows
4. Lines that contain mostly numbers and no alphabetic content (except short strings) are continuation data

```php
private function detectTableHeader(array $lines): ?int
{
    $headerKeywords = ['item', 'product', 'particular', 'batch', 'exp', 'mrp', 'rate', 'qty', 'hsn', 'pack'];

    foreach ($lines as $index => $line) {
        $lower = strtolower($line);
        $matchCount = 0;
        foreach ($headerKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                $matchCount++;
            }
        }
        if ($matchCount >= 3) {
            return $index;
        }
    }
    return null;
}
```

**Phase 3: Column Alignment (Positional Parsing)**

If Phase 2 finds the header, use the column positions (character indices) of header labels to determine which data goes in which column. This is brittle with OCR text because spacing is not always preserved, which is exactly why the LLM approach is recommended instead.

**Phase 4: Value Normalization**

```php
// Expiry date normalization: "06/27" -> "2027-06-01", "JUN-27" -> "2027-06-01"
private function normalizeExpiryDate(string $raw): ?string
{
    // MM/YY
    if (preg_match('/^(\d{1,2})\s*[\/\-]\s*(\d{2})$/', $raw, $m)) {
        $month = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $year = '20' . $m[2];
        return "{$year}-{$month}-01";
    }
    // MM/YYYY
    if (preg_match('/^(\d{1,2})\s*[\/\-]\s*(\d{4})$/', $raw, $m)) {
        $month = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        return "{$m[2]}-{$month}-01";
    }
    // MON-YY (JUN-27)
    if (preg_match('/^([A-Z]{3})\s*[\-\/]\s*(\d{2,4})$/i', $raw, $m)) {
        $month = Carbon::parse("1 {$m[1]} 2000")->format('m');
        $year = strlen($m[2]) === 2 ? '20' . $m[2] : $m[2];
        return "{$year}-{$month}-01";
    }
    return null;
}

// Amount normalization: "3,210.00" -> 3210.00, "32.10" -> 32.10
private function normalizeAmount(string $raw): ?float
{
    $cleaned = preg_replace('/[^0-9.]/', '', $raw);
    return is_numeric($cleaned) ? (float)$cleaned : null;
}
```

---

## 6. Database Changes

### New Table: `invoice_scans`

```sql
-- ============================================
-- INVOICE SCANS (OCR scan history + audit)
-- ============================================
CREATE TABLE invoice_scans (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    purchase_id     BIGINT UNSIGNED NULL,          -- Links to purchase if created from this scan
    image_path      VARCHAR(500) NOT NULL,          -- Relative storage path
    status          ENUM('processing','completed','partial','failed') DEFAULT 'processing',
    raw_ocr_text    LONGTEXT NULL,                  -- Full OCR output for debugging
    ocr_confidence  DECIMAL(5,4) NULL,              -- 0.0000 to 1.0000
    extracted_data  JSON NULL,                       -- Parsed structured data (see response format above)
    warnings        JSON NULL,                       -- Array of warning messages
    error_message   TEXT NULL,                       -- Error details if failed
    processing_ms   INT UNSIGNED NULL,               -- Processing time in milliseconds
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL,
    INDEX idx_invoice_scans_tenant (tenant_id, created_at),
    INDEX idx_invoice_scans_status (tenant_id, status)
);
```

**Why store the scan record:**
- **Audit trail:** Know when and who scanned what, even if the purchase was corrected manually
- **Debugging:** When OCR gets something wrong, the raw_ocr_text is invaluable for improving the parser
- **Linkage:** `purchase_id` links scan to the purchase created from it (set after purchase is saved)
- **Analytics:** Track scan success rates, usage per tenant, identify common parsing failures
- **Compliance:** Indian pharmacy records require good audit trails

### New Permission

Add to `PermissionSeeder.php`:
```php
['name' => 'purchases.scan_invoice', 'module' => 'purchases'],
```

Grant to roles: `owner`, `store_manager`, `pharmacist`.

### Migration File

New migration: `database/migrations/XXXX_XX_XX_create_invoice_scans_table.php`

### Storage

Images stored at: `storage/app/private/invoice-scans/{tenant_id}/{uuid}.{ext}`

- `private` disk: Not web-accessible (security -- invoices may contain GSTIN, addresses)
- Served via a controller route with auth + tenant check if user wants to view the original image
- Cleanup scheduled command: Delete images older than 90 days (configurable via `config/medistock.php`)

### Table Count Impact

Current: 27 tables. After this feature: **28 tables** (only `invoice_scans` added).

---

## 7. Frontend UX Design (Alpine.js Based)

### Blade View Location

File: `resources/views/pages/purchases/create.blade.php` (existing file, modified)

New partial: `resources/views/components/invoice-scanner.blade.php`

### UX Flow Design

#### Step 1: Scan Trigger Button

At the top of the existing purchase creation form, add:

```html
<!-- Component: invoice-scanner.blade.php -->
<div x-data="invoiceScanner()" class="mb-6">
    
    <!-- Scan Button (prominent, mobile-friendly) -->
    <div x-show="!scanning && !scanned" class="flex gap-3">
        <label class="flex-1 flex items-center justify-center gap-2 px-4 py-3 
                       bg-indigo-600 text-white rounded-lg cursor-pointer 
                       hover:bg-indigo-700 active:bg-indigo-800 
                       text-base font-medium transition">
            <!-- Camera icon (Heroicon) -->
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Scan Invoice
            <input type="file" 
                   accept="image/*" 
                   capture="environment"
                   class="hidden"
                   @change="handleImageSelect($event)">
        </label>
        
        <span class="text-sm text-gray-500 self-center">or enter manually below</span>
    </div>

    <!-- Image Preview + Processing State -->
    <div x-show="scanning" class="relative">
        <div class="bg-gray-100 rounded-lg p-4 flex items-center gap-4">
            <img :src="previewUrl" class="w-20 h-20 object-cover rounded" alt="Invoice preview">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="animate-spin h-5 w-5 text-indigo-600" viewBox="0 0 24 24">
                        <!-- spinner -->
                    </svg>
                    <span class="font-medium text-gray-900">Scanning invoice...</span>
                </div>
                <p class="text-sm text-gray-500">Extracting text and matching items</p>
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                    <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-1000"
                         :style="'width: ' + progress + '%'"></div>
                </div>
            </div>
            <button @click="cancelScan()" class="text-gray-400 hover:text-gray-600">
                <!-- X icon -->
            </button>
        </div>
    </div>

    <!-- Success / Partial Result Banner -->
    <div x-show="scanned" class="rounded-lg p-3 flex items-center justify-between"
         :class="scanResult.confidence_score > 0.7 ? 'bg-green-50 border border-green-200' : 'bg-amber-50 border border-amber-200'">
        <div class="flex items-center gap-2">
            <template x-if="scanResult.confidence_score > 0.7">
                <svg class="w-5 h-5 text-green-600"><!-- check icon --></svg>
            </template>
            <template x-if="scanResult.confidence_score <= 0.7">
                <svg class="w-5 h-5 text-amber-600"><!-- warning icon --></svg>
            </template>
            <span class="text-sm font-medium" x-text="scanStatusMessage"></span>
        </div>
        <div class="flex gap-2">
            <button @click="retake()" class="text-sm text-gray-600 hover:text-gray-800 underline">
                Retake
            </button>
            <button @click="viewOriginal()" class="text-sm text-indigo-600 hover:text-indigo-800 underline">
                View Original
            </button>
        </div>
    </div>

    <!-- Warnings List -->
    <template x-if="warnings.length > 0">
        <div class="mt-2 bg-amber-50 border border-amber-200 rounded-lg p-3">
            <p class="text-sm font-medium text-amber-800 mb-1">Please verify:</p>
            <ul class="text-sm text-amber-700 list-disc list-inside">
                <template x-for="warning in warnings">
                    <li x-text="warning"></li>
                </template>
            </ul>
        </div>
    </template>
</div>
```

#### Step 2: Alpine.js Component Logic

```javascript
function invoiceScanner() {
    return {
        scanning: false,
        scanned: false,
        previewUrl: null,
        progress: 0,
        scanResult: null,
        warnings: [],
        scanStatusMessage: '',

        async handleImageSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Client-side validation
            if (file.size > 5 * 1024 * 1024) {
                alert('Image is too large (max 5 MB). Please try again.');
                return;
            }

            // Show preview
            this.previewUrl = URL.createObjectURL(file);
            this.scanning = true;
            this.progress = 10;

            try {
                // Compress image client-side
                const compressed = await this.compressImage(file, 1600, 0.85);
                this.progress = 30;

                // Upload and scan
                const formData = new FormData();
                formData.append('image', compressed, 'invoice.jpg');

                const response = await fetch('/api/v1/invoice-scans', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                this.progress = 90;

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Scan failed');
                }

                const result = await response.json();
                this.progress = 100;
                this.scanResult = result.data;
                this.warnings = result.data.warnings || [];

                // Pre-fill the purchase form
                this.prefillForm(result.data.extracted);

                this.scanStatusMessage = result.data.confidence_score > 0.7
                    ? 'Invoice scanned successfully. Please review the values below.'
                    : 'Partial scan. Some fields need manual entry. Please review carefully.';

                this.scanning = false;
                this.scanned = true;

            } catch (error) {
                this.scanning = false;
                alert('Scan failed: ' + error.message + '\nYou can enter the purchase manually.');
            }
        },

        prefillForm(extracted) {
            // This method dispatches a custom event that the purchase form's 
            // Alpine component listens for. This keeps the scanner decoupled 
            // from the form.
            window.dispatchEvent(new CustomEvent('invoice-scanned', {
                detail: extracted
            }));
        },

        async compressImage(file, maxDim, quality) {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let { width, height } = img;

                    if (width > maxDim || height > maxDim) {
                        const ratio = Math.min(maxDim / width, maxDim / height);
                        width *= ratio;
                        height *= ratio;
                    }

                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob(resolve, 'image/jpeg', quality);
                };
                img.src = URL.createObjectURL(file);
            });
        },

        retake() {
            this.scanned = false;
            this.scanning = false;
            this.scanResult = null;
            this.warnings = [];
            // Reset form fields that were pre-filled
            window.dispatchEvent(new CustomEvent('invoice-scan-reset'));
        },

        cancelScan() {
            this.scanning = false;
            // AbortController for fetch would go here in production
        },

        viewOriginal() {
            // Open original image in a modal or new tab
            if (this.scanResult?.image_url) {
                window.open(this.scanResult.image_url, '_blank');
            }
        }
    };
}
```

#### Step 3: Purchase Form Listens for Scan Event

In the existing purchase form Alpine component:

```javascript
// Inside the purchaseForm() Alpine component (already exists)
init() {
    // Listen for invoice scan data
    window.addEventListener('invoice-scanned', (event) => {
        const data = event.detail;
        this.populateFromScan(data);
    });
    
    window.addEventListener('invoice-scan-reset', () => {
        this.resetForm();
    });
},

populateFromScan(data) {
    // Supplier
    if (data.supplier?.matched_supplier_id) {
        this.supplier_id = data.supplier.matched_supplier_id;
        // Trigger supplier dropdown to show selected
    }

    // Invoice header
    this.invoice_number = data.invoice_number || '';
    this.invoice_date = data.invoice_date || '';

    // Items
    this.items = data.items.map((item, index) => ({
        item_id: item.matched_item_id || null,
        item_name_display: item.item_name || '',
        batch_number: item.batch_number || '',
        expiry_date: item.expiry_date || '',
        mrp: item.mrp || '',
        purchase_price: item.purchase_price || '',
        quantity: item.quantity || '',
        free_quantity: item.free_quantity || 0,
        gst_percent: item.gst_percent || '',
        discount_percent: item.discount_percent || 0,
        // Confidence indicators for UI highlighting
        _confidence: item.field_confidence || {},
        _unmatched: !item.matched_item_id,
    }));

    // Totals (for verification)
    this._scanned_totals = data.totals;

    this.recalculateTotals();
},
```

#### Visual Confidence Indicators

```html
<!-- In the item row template, apply conditional classes based on confidence -->
<input type="text"
       x-model="item.batch_number"
       :class="{
           'border-green-300 bg-green-50': item._confidence.batch_number === 'high',
           'border-amber-300 bg-amber-50': item._confidence.batch_number === 'medium',
           'border-red-300 bg-red-50': item._confidence.batch_number === 'low' || !item.batch_number,
           'border-gray-300': !item._confidence.batch_number
       }"
       class="w-full px-2 py-1.5 text-sm rounded border focus:ring-1 focus:ring-indigo-500">
```

Color key (shown as a small legend above the items table):
- Green border: High confidence (likely correct)
- Amber border: Medium confidence (please verify)
- Red border: Low confidence or missing (needs manual entry)

---

## 8. Error Handling and Edge Cases

### Error Categories and Handling

| Scenario | Detection | User-Facing Behavior | Backend Handling |
|----------|-----------|---------------------|------------------|
| **No text detected** | Vision API returns empty `fullTextAnnotation` | "No text found. Please ensure the image is clear and well-lit." | `InvoiceScan.status = 'failed'`, log error |
| **Blurry/dark image** | OCR confidence < 0.4 | "Image quality too low. Please retake in better lighting." | `InvoiceScan.status = 'failed'` |
| **Partial extraction** | Some fields parsed, others null | Form partially filled. Amber banner: "Some fields need manual entry." | `InvoiceScan.status = 'partial'` |
| **Wrong document type** | OCR text does not contain invoice keywords | "This does not appear to be an invoice. Please scan a supplier invoice." | `InvoiceScan.status = 'failed'` |
| **Handwritten invoice** | Low confidence + sparse text | Partial fill with many amber/red fields | `InvoiceScan.status = 'partial'` |
| **Image too large** | Client-side check > 5MB before upload | "Image is too large. Taking a lower-resolution photo." | 422 validation error |
| **Google Vision API down** | HTTP 5xx from Google | "Scan service temporarily unavailable. Please try again or enter manually." | Log error, circuit breaker after 3 failures in 5 min |
| **Google Vision API quota exceeded** | HTTP 429 from Google | "Daily scan limit reached. Please enter manually." | Log, alert admin |
| **API key invalid/expired** | HTTP 403 from Google | "Scan service not configured. Contact admin." | Log critical error |
| **Network timeout** | Google API > 30s response | "Scan timed out. Please try again." | Retry once, then fail |
| **Duplicate invoice** | `invoice_number` + `supplier_id` unique check | Yellow warning: "Invoice INV-123 from Supplier ABC may already exist." Non-blocking. | Check after parsing, before returning |
| **Item not in master** | `ItemMatchingService` returns null | Item row has red "Unmatched" badge + "Create New" link | Include in `warnings` array |
| **Supplier not found** | `matchSupplier` returns null | Supplier dropdown blank, user must select manually | Supplier section of response is null |
| **Multiple pages** | Only first page image can be scanned | "Only first page processed. Add remaining items manually." | Document in warnings |
| **Rotated image** | Client-side EXIF auto-rotation should handle | Usually transparent to user | If Vision API confidence is very low, suggest "Try rotating the image" |
| **LLM parsing fails** (if using LLM parser) | Claude API returns error or malformed JSON | Fall back to regex parser. If that also fails, return raw OCR text only. | Log parsing failure, use fallback |

### Circuit Breaker Pattern

To avoid hammering Google's API when it is down:

```php
class GoogleVisionOCRService implements OCRServiceInterface
{
    private const CIRCUIT_BREAKER_KEY = 'google_vision_circuit_breaker';
    private const FAILURE_THRESHOLD = 3;
    private const RECOVERY_TIMEOUT = 300; // 5 minutes

    public function detectDocumentText(string $imageContent): OCRResult
    {
        // Check circuit breaker
        $failures = Cache::get(self::CIRCUIT_BREAKER_KEY, 0);
        if ($failures >= self::FAILURE_THRESHOLD) {
            throw new OCRException('OCR service temporarily unavailable. Please try again later.');
        }

        try {
            $result = $this->callVisionApi($imageContent);
            // Reset circuit breaker on success
            Cache::forget(self::CIRCUIT_BREAKER_KEY);
            return $result;
        } catch (\Exception $e) {
            // Increment failure count
            Cache::put(
                self::CIRCUIT_BREAKER_KEY,
                $failures + 1,
                now()->addSeconds(self::RECOVERY_TIMEOUT)
            );
            throw $e;
        }
    }
}
```

### Validation Before Returning to Frontend

```php
// In InvoiceScanService, validate the parsed data has minimum viable content
private function validateParsedData(ParsedInvoiceDTO $parsed): bool
{
    // Must have at least one item with a name
    $hasItems = collect($parsed->items)->contains(fn($item) => !empty($item->itemName));
    
    // Must have invoice number OR date
    $hasHeader = !empty($parsed->invoiceNumber) || !empty($parsed->invoiceDate);
    
    return $hasItems || $hasHeader;
}
```

---

## 9. Cost Estimation for Google Cloud Vision

### Pricing (as of 2026)

| Tier | Volume | Cost per 1000 images | Monthly Cost |
|------|--------|---------------------|--------------|
| **Free tier** | 0 - 1,000 images/month | **$0.00** | **$0.00** |
| Tier 1 | 1,001 - 5,000,000 | $1.50 per 1,000 | Varies |
| Tier 2 | 5,000,001+ | $0.60 per 1,000 | Varies |

### Usage Projections for MediStock Pro

| Scenario | Scans/Tenant/Month | Tenants | Total Scans/Month | Monthly Cost |
|----------|--------------------|---------|--------------------|-------------|
| **Early (5 tenants)** | 30 | 5 | 150 | **$0.00** (free tier) |
| **Growth (20 tenants)** | 40 | 20 | 800 | **$0.00** (free tier) |
| **At free limit** | 50 | 20 | 1,000 | **$0.00** (exactly free) |
| **Medium (50 tenants)** | 40 | 50 | 2,000 | **$1.50** |
| **Scale (100 tenants)** | 50 | 100 | 5,000 | **$6.00** |
| **Large (500 tenants)** | 50 | 500 | 25,000 | **$36.00** |

**Key insight:** At the early stage (under 20 tenants), this feature is essentially **free**. Even at 500 tenants doing aggressive scanning, the Google Vision cost is only ~$36/month.

### If Using Claude Haiku for Parsing (Additional Cost)

| Volume | Avg Input Tokens (OCR text) | Avg Output Tokens (JSON) | Input Cost | Output Cost | Total/Month |
|--------|---------------------------|------------------------|------------|-------------|-------------|
| 1,000 scans | ~1,500 tokens each | ~800 tokens each | $0.0015 | $0.004 | **$5.50** |
| 5,000 scans | ~1,500 tokens each | ~800 tokens each | $0.0075 | $0.020 | **$27.50** |
| 25,000 scans | ~1,500 tokens each | ~800 tokens each | $0.0375 | $0.100 | **$137.50** |

**Combined cost (Vision + Haiku) for 1,000 scans/month: approximately $5.50 total.** Negligible.

### Tenant-Level Rate Limiting

To control costs and prevent abuse:

```php
// In config/medistock.php
'invoice_scan' => [
    'daily_limit_per_tenant' => 20,    // Max 20 scans per tenant per day
    'monthly_limit_per_tenant' => 200, // Max 200 scans per tenant per month
    'max_image_size_mb' => 5,
],
```

Enforce via middleware or in `InvoiceScanService`:

```php
private function checkRateLimit(int $tenantId): void
{
    $todayCount = InvoiceScan::where('tenant_id', $tenantId)
        ->whereDate('created_at', today())
        ->count();

    if ($todayCount >= config('medistock.invoice_scan.daily_limit_per_tenant')) {
        throw new RateLimitException('Daily scan limit reached. You can scan again tomorrow or enter the purchase manually.');
    }
}
```

---

## 10. LLM Parsing vs Regex Parsing -- Comprehensive Comparison

### The Core Problem

Given OCR text like this:

```
ABC DISTRIBUTORS
123, MG Road, Mumbai - 400001
GSTIN: 27AABCU9603R1ZM    DL No: MH/20B/12345

TAX INVOICE
Inv No: INV-2026-001234           Date: 28-02-2026

Sr  Particulars          Pack    HSN     Batch   Exp    Qty  Free  MRP    Rate   Disc%  Amt     CGST  SGST
1   Paracetamol 500mg    10x10   30049099 BT26A  06/27  100  10   45.50  32.10  10.0   3210.00 6.0   6.0
    Tab IP
2   Amoxicillin Cap      10x10   30041090 AM26B  03/28   50   5   85.00  60.00  15.0   3000.00 6.0   6.0
    250mg IP
3   Omeprazole 20mg      10x10   30049099 OM27C  12/27   80   0   32.00  22.50   5.0   1800.00 6.0   6.0
    Cap IP

                                            Subtotal:  8010.00
                                            CGST 6%:    480.60
                                            SGST 6%:    480.60
                                            Total:     8971.20
```

Extract this into structured JSON.

### Approach A: Regex-Based Parser

**Implementation effort:** ~400-600 lines of PHP, extensive testing needed.

**How it works:**
1. Line-by-line regex matching for header fields (GSTIN, Invoice No, Date)
2. Table header detection by keyword matching
3. Column-position heuristics (character index alignment)
4. Per-field regex patterns for batch numbers, expiry dates, amounts
5. Special handling for multi-line item names (item name spans 2 lines)

**Strengths:**
- Zero external API cost
- Zero latency (runs locally)
- Deterministic -- same input always produces same output
- No API dependency (works offline)
- Full control over parsing logic

**Weaknesses:**
- **Extremely brittle:** Each distributor formats invoices differently. Column ordering, spacing, label variations are enormous.
- **Multi-line items:** "Paracetamol 500mg\nTab IP" -- detecting that "Tab IP" is continuation of item name, not a new row, is very hard with regex.
- **OCR artifacts:** Spaces inserted/removed unpredictably. "32.10" might OCR as "32. 10" or "3210". Column alignment breaks.
- **Maintenance burden:** Every new distributor format encountered may require new regex patterns. Ongoing whack-a-mole.
- **No semantic understanding:** Cannot infer that "P.Rate" means "Purchase Rate" in context. Each synonym must be explicitly coded.
- **Estimated accuracy on diverse invoices:** 50-70% of fields correct without manual tuning per supplier.
- **Cannot handle OCR noise gracefully:** If a digit is misread (e.g., "45.5O" with letter O instead of zero), regex extraction breaks.

### Approach B: LLM-Based Parser (Claude Haiku 4.5)

**Implementation effort:** ~80-120 lines of PHP.

**How it works:**
1. Send the raw OCR text to Claude Haiku API with a carefully crafted system prompt
2. The prompt describes the exact JSON schema expected
3. Claude understands the invoice context, handles variations, multi-line items, and OCR artifacts
4. Returns structured JSON directly

**The prompt:**

```php
class LLMInvoiceParser implements InvoiceParserInterface
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are an Indian pharmacy invoice parser. You receive raw OCR text from a scanned pharmaceutical distributor/wholesale invoice. Extract the structured data into the exact JSON format specified.

CONTEXT:
- These are Indian pharmaceutical wholesale/distributor invoices
- They contain GSTIN (15-char alphanumeric), Drug License numbers, HSN codes
- Item names are medicine names (e.g., "Paracetamol 500mg Tab", "Amoxicillin 250mg Cap")
- Batch numbers are alphanumeric codes
- Expiry dates are usually MM/YY or MM/YYYY format
- MRP is Maximum Retail Price, Rate/P.Rate is the purchase price
- GST can appear as combined GST%, or split CGST% + SGST%, or IGST% (for interstate)
- Item names may span multiple lines in OCR output (e.g., "Paracetamol 500mg\nTab IP")
- Free/bonus quantities may appear in a separate column or same line as quantity

RULES:
1. Extract ALL items from the invoice table, even if some fields are unclear
2. For expiry dates, normalize to YYYY-MM format (e.g., "06/27" -> "2027-06")
3. For GST: if CGST and SGST are given separately, combine them (CGST 6% + SGST 6% = GST 12%)
4. If a field cannot be determined, use null
5. For item names spanning multiple lines, combine them into one name
6. Amounts should be numbers (not strings), without commas
7. If "Free" or "Fre" column exists, extract free quantity separately from main quantity

OUTPUT FORMAT (strict JSON, no markdown, no explanation):
PROMPT;

    private const USER_PROMPT_TEMPLATE = <<<'PROMPT'
Parse this invoice OCR text and return ONLY the JSON (no other text):

```
%s
```

Return this exact JSON structure:
{
  "supplier_name": "string or null",
  "supplier_gstin": "string or null",
  "supplier_drug_license": "string or null",
  "invoice_number": "string or null",
  "invoice_date": "YYYY-MM-DD or null",
  "items": [
    {
      "row_index": 1,
      "item_name": "string",
      "batch_number": "string or null",
      "expiry_date": "YYYY-MM or null",
      "mrp": number_or_null,
      "purchase_price": number_or_null,
      "quantity": number_or_null,
      "free_quantity": number_or_null,
      "gst_percent": number_or_null,
      "discount_percent": number_or_null,
      "hsn_code": "string or null",
      "pack_size": "string or null"
    }
  ],
  "subtotal": number_or_null,
  "gst_amount": number_or_null,
  "discount_amount": number_or_null,
  "total_amount": number_or_null
}
PROMPT;

    public function parse(string $ocrText): ParsedInvoiceDTO
    {
        $response = Http::timeout(20)
            ->withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-haiku-4-5-20241022',
                'max_tokens' => 2048,
                'system' => self::SYSTEM_PROMPT,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => sprintf(self::USER_PROMPT_TEMPLATE, $ocrText),
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new ParsingException('LLM parsing failed: ' . $response->status());
        }

        $content = $response->json('content.0.text');
        $parsed = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to extract JSON from response if wrapped in markdown
            if (preg_match('/\{[\s\S]+\}/', $content, $match)) {
                $parsed = json_decode($match[0], true);
            }
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ParsingException('LLM returned invalid JSON');
            }
        }

        return $this->mapToDTO($parsed);
    }
}
```

**Strengths:**
- **Handles variation:** Works across different distributor formats without any per-supplier tuning
- **Multi-line items:** Naturally understands "Paracetamol 500mg\nTab IP" is one item name
- **OCR artifact tolerance:** Can interpret "45.5O" as 45.50, understands context
- **Semantic understanding:** Knows "P.Rate" = purchase rate, "MRP" = maximum retail price, "Fre" = free
- **Minimal code:** ~100 lines vs ~500 lines for regex
- **Self-correcting:** As Claude models improve, parsing accuracy improves with zero code changes
- **Estimated accuracy on diverse invoices:** 85-95% of fields correct
- **Handles edge cases inherently:** Totals, subtotals, CGST/SGST combination, date normalization

**Weaknesses:**
- Additional API cost (but ~$5.50/month for 1,000 scans -- negligible)
- Additional latency: ~1-3 seconds for Haiku response (total OCR + parse = 5-10 seconds)
- Non-deterministic (though with temperature=0 it is very consistent)
- External dependency (Claude API could be down)
- Cannot work offline

### Side-by-Side Comparison

| Criterion | Regex Parser | LLM Parser (Haiku) |
|-----------|-------------|-------------------|
| **Accuracy (varied invoices)** | 50-70% | 85-95% |
| **Implementation effort** | 3-5 days | 0.5-1 day |
| **Maintenance effort** | High (ongoing) | Very low |
| **Per-scan cost** | $0.00 | ~$0.0055 |
| **Monthly cost (1K scans)** | $0.00 | ~$5.50 |
| **Latency added** | ~0ms | ~1-3 seconds |
| **External dependency** | None | Anthropic API |
| **Handles new formats** | Needs code changes | Automatically |
| **Multi-line item names** | Very hard | Trivial |
| **OCR artifact tolerance** | Low | High |
| **Offline capability** | Yes | No |
| **Deterministic** | Yes | Mostly (temp=0) |

### Recommendation: Hybrid Approach

**Primary parser: LLM (Claude Haiku 4.5).** The accuracy difference is dramatic (85-95% vs 50-70%), and the cost is negligible.

**Fallback parser: Regex.** If the Claude API is down or rate-limited, fall back to the regex parser. This ensures the feature always works, even if with lower accuracy.

**Implementation:**

```php
// In AppServiceProvider or a dedicated ServiceProvider:
$this->app->bind(InvoiceParserInterface::class, function ($app) {
    return new FallbackInvoiceParser(
        primary: new LLMInvoiceParser(),
        fallback: new RegexInvoiceParser(),
    );
});
```

```php
class FallbackInvoiceParser implements InvoiceParserInterface
{
    public function __construct(
        private InvoiceParserInterface $primary,
        private InvoiceParserInterface $fallback,
    ) {}

    public function parse(string $ocrText): ParsedInvoiceDTO
    {
        try {
            return $this->primary->parse($ocrText);
        } catch (\Exception $e) {
            Log::warning('Primary invoice parser failed, using fallback', [
                'error' => $e->getMessage(),
            ]);
            return $this->fallback->parse($ocrText);
        }
    }
}
```

### Environment Config

```env
# .env
ANTHROPIC_API_KEY=sk-ant-...
INVOICE_PARSER_DRIVER=llm      # 'llm' or 'regex'
```

```php
// config/services.php
'anthropic' => [
    'api_key' => env('ANTHROPIC_API_KEY'),
],

// config/medistock.php
'invoice_scan' => [
    'parser_driver' => env('INVOICE_PARSER_DRIVER', 'llm'),
    'daily_limit_per_tenant' => 20,
    'monthly_limit_per_tenant' => 200,
    'max_image_size_mb' => 5,
    'image_retention_days' => 90,
],
```

---

## Implementation Sequencing

### Phase 1 (2-3 days): Core Infrastructure
1. Create `invoice_scans` migration and model
2. Add `purchases.scan_invoice` permission
3. Create `GoogleVisionOCRService` (REST API, no gRPC)
4. Create `InvoiceScanController` with `StoreInvoiceScanRequest`
5. Wire up routes

### Phase 2 (2-3 days): Parsing + Matching
1. Create `LLMInvoiceParser` with system prompt
2. Create `RegexInvoiceParser` as fallback
3. Create `FallbackInvoiceParser` wrapper
4. Create `ItemMatchingService` with fuzzy matching
5. Create `InvoiceScanService` orchestrator
6. Create `ParsedInvoiceDTO` and `ParsedInvoiceItemDTO`

### Phase 3 (2-3 days): Frontend
1. Create `invoice-scanner.blade.php` component
2. Create `invoiceScanner()` Alpine.js component
3. Integrate with existing purchase form (event dispatch)
4. Add confidence-based field highlighting
5. Add warnings display
6. Mobile testing (camera capture, compression)

### Phase 4 (1-2 days): Polish
1. Rate limiting
2. Circuit breaker on Google Vision
3. Scan history page (simple list)
4. Scheduled command to purge old images
5. Testing with 10+ real invoice samples from different distributors

**Total estimated effort: 7-11 days.**

---

## Summary of All New Files

```
app/
  Http/
    Controllers/Api/V1/InvoiceScanController.php
    Requests/StoreInvoiceScanRequest.php
    Resources/InvoiceScanResource.php
  Services/
    InvoiceScanService.php
    OCR/
      OCRServiceInterface.php
      OCRResult.php
      OCRException.php
      GoogleVisionOCRService.php
    InvoiceParser/
      InvoiceParserInterface.php
      ParsedInvoiceDTO.php
      ParsedInvoiceItemDTO.php
      LLMInvoiceParser.php
      RegexInvoiceParser.php
      FallbackInvoiceParser.php
      ParsingException.php
    ItemMatchingService.php
  Models/
    InvoiceScan.php
  Exceptions/
    InvoiceScanException.php
    RateLimitException.php
  Console/Commands/
    PurgeOldInvoiceScans.php

database/
  migrations/
    XXXX_XX_XX_create_invoice_scans_table.php

resources/
  views/
    components/
      invoice-scanner.blade.php

config/
  services.php            (add google_vision + anthropic sections)
  medistock.php           (add invoice_scan section)
```

---

### Critical Files for Implementation
- `c:\Working\MediStock Pro\ARCHITECTURE.md` - Contains the complete database schema for purchases, purchase_items, batches, suppliers tables and the existing folder structure, API endpoint conventions, and service layer patterns that must be followed
- `app/Services/InvoiceScanService.php` (to be created) - The orchestrator service that ties OCR, parsing, supplier matching, and item matching together; this is the core business logic file
- `app/Services/InvoiceParser/LLMInvoiceParser.php` (to be created) - The Claude Haiku-based parser with the carefully crafted system prompt; accuracy of the entire feature depends on this prompt engineering
- `app/Services/OCR/GoogleVisionOCRService.php` (to be created) - The Google Cloud Vision REST API wrapper that must work without gRPC extension on Hostinger shared hosting
- `resources/views/components/invoice-scanner.blade.php` (to be created) - The Alpine.js-based frontend component handling camera capture, image compression, upload, progress indication, and event dispatch to the existing purchase form