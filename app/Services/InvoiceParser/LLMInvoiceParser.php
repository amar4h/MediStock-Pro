<?php

namespace App\Services\InvoiceParser;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Invoice parser using Claude Haiku API for intelligent text extraction.
 *
 * Sends OCR text to Claude Haiku with a specialized system prompt for
 * Indian pharmacy invoice parsing. Receives a structured JSON response
 * and maps it to the ParsedInvoiceDTO.
 */
class LLMInvoiceParser implements InvoiceParserInterface
{
    private const API_ENDPOINT = 'https://api.anthropic.com/v1/messages';
    private const MODEL = 'claude-3-5-haiku-20241022';
    private const MAX_TOKENS = 4096;
    private const TIMEOUT_SECONDS = 60;

    /**
     * Parse OCR text into a structured invoice using Claude Haiku.
     *
     * @param  string  $ocrText  Raw OCR-extracted text
     * @return ParsedInvoiceDTO
     *
     * @throws ParsingException If the LLM call fails or response is invalid
     */
    public function parse(string $ocrText): ParsedInvoiceDTO
    {
        $apiKey = config('services.anthropic.api_key');

        if (empty($apiKey)) {
            throw ParsingException::llmFailure('Anthropic API key not configured');
        }

        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($ocrText);

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withHeaders([
                    'x-api-key'         => $apiKey,
                    'anthropic-version'  => '2023-06-01',
                    'Content-Type'       => 'application/json',
                ])
                ->post(self::API_ENDPOINT, [
                    'model'      => self::MODEL,
                    'max_tokens' => self::MAX_TOKENS,
                    'system'     => $systemPrompt,
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                ]);

            if ($response->failed()) {
                $errorMessage = $response->json('error.message', 'Unknown API error');
                throw ParsingException::llmFailure("HTTP {$response->status()}: {$errorMessage}");
            }

            $content = $response->json('content.0.text', '');

            return $this->parseJsonResponse($content);

        } catch (ParsingException $e) {
            throw $e;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw ParsingException::llmFailure('Connection timeout', $e);
        } catch (\Throwable $e) {
            Log::error('LLM Invoice Parser unexpected error', [
                'message' => $e->getMessage(),
            ]);
            throw ParsingException::llmFailure($e->getMessage(), $e);
        }
    }

    /**
     * Build the system prompt for Indian pharmacy invoice parsing.
     */
    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert at parsing Indian pharmaceutical purchase invoices. You extract structured data from OCR text of pharmacy invoices.

Key domain knowledge:
- Indian pharma invoices have GSTIN (15-character alphanumeric, e.g., 27AABCU9603R1ZQ)
- Drug License numbers (e.g., MH-MUM-123456, DL-20B-12345)
- HSN codes are 4-8 digit numbers for pharmaceuticals (typically 3001-3006)
- GST rates for medicines: 0%, 5%, 12%, 18%
- Expiry dates come in formats: MM/YY, MM/YYYY, MON-YY (e.g., 06/25, 06/2025, JUN-25)
- Batch numbers are alphanumeric (e.g., BN1234, ABT-2301)
- MRP = Maximum Retail Price (printed on product)
- PTR/PTS = Price to Retailer/Stockist (purchase price)
- Free/Bonus items are common (e.g., "10+2" means 10 purchased + 2 free)
- Pack sizes like "10x10" (10 strips of 10 tablets)

Always normalize:
- Dates to YYYY-MM-DD format
- Amounts to decimal numbers (strip commas and currency symbols)
- GSTIN to uppercase without spaces

Respond ONLY with valid JSON matching the exact schema provided. No explanations, no markdown.
PROMPT;
    }

    /**
     * Build the user prompt with OCR text and expected JSON schema.
     */
    private function buildUserPrompt(string $ocrText): string
    {
        return <<<PROMPT
Parse the following OCR text from an Indian pharmaceutical purchase invoice and extract structured data.

OCR TEXT:
---
{$ocrText}
---

Return a JSON object with EXACTLY this schema (use null for fields that cannot be determined):
{
  "supplier_name": "string or null",
  "supplier_gstin": "string or null (15-char GSTIN)",
  "supplier_drug_license": "string or null",
  "invoice_number": "string or null",
  "invoice_date": "YYYY-MM-DD or null",
  "subtotal": number or null,
  "gst_amount": number or null,
  "discount_amount": number or null,
  "total_amount": number or null,
  "items": [
    {
      "row_index": 0,
      "item_name": "string or null",
      "batch_number": "string or null",
      "expiry_date": "YYYY-MM-DD or null",
      "mrp": number or null,
      "purchase_price": number or null,
      "quantity": integer or null,
      "free_quantity": integer or null (0 if none),
      "gst_percent": number or null,
      "discount_percent": number or null,
      "hsn_code": "string or null",
      "pack_size": "string or null"
    }
  ]
}

Important rules:
1. If a field cannot be determined from the text, use null
2. Normalize all dates to YYYY-MM-DD (e.g., "06/25" becomes "2025-06-01", "JAN-26" becomes "2026-01-01")
3. Strip commas from numbers (e.g., "1,234.56" becomes 1234.56)
4. For quantity, only count purchased quantity (not free/bonus)
5. Free/bonus quantity goes in free_quantity
6. purchase_price should be the rate/PTR per unit
7. Return ONLY the JSON, no explanation
PROMPT;
    }

    /**
     * Parse the JSON response from the LLM, handling markdown-wrapped JSON.
     */
    private function parseJsonResponse(string $content): ParsedInvoiceDTO
    {
        $content = trim($content);

        // Handle markdown-wrapped JSON (```json ... ```)
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?```/s', $content, $matches)) {
            $content = trim($matches[1]);
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ParsingException::invalidJson($content);
        }

        // Map items to DTOs
        $items = [];
        foreach ($data['items'] ?? [] as $index => $itemData) {
            $items[] = new ParsedInvoiceItemDTO(
                rowIndex:        $itemData['row_index'] ?? $index,
                itemName:        $itemData['item_name'] ?? null,
                batchNumber:     $itemData['batch_number'] ?? null,
                expiryDate:      $itemData['expiry_date'] ?? null,
                mrp:             isset($itemData['mrp']) ? (float) $itemData['mrp'] : null,
                purchasePrice:   isset($itemData['purchase_price']) ? (float) $itemData['purchase_price'] : null,
                quantity:        isset($itemData['quantity']) ? (int) $itemData['quantity'] : null,
                freeQuantity:    isset($itemData['free_quantity']) ? (int) $itemData['free_quantity'] : null,
                gstPercent:      isset($itemData['gst_percent']) ? (float) $itemData['gst_percent'] : null,
                discountPercent: isset($itemData['discount_percent']) ? (float) $itemData['discount_percent'] : null,
                hsnCode:         $itemData['hsn_code'] ?? null,
                packSize:        $itemData['pack_size'] ?? null,
                fieldConfidence: [], // LLM parser does not provide per-field confidence
            );
        }

        return new ParsedInvoiceDTO(
            supplierName:       $data['supplier_name'] ?? null,
            supplierGstin:      $data['supplier_gstin'] ?? null,
            supplierDrugLicense: $data['supplier_drug_license'] ?? null,
            invoiceNumber:      $data['invoice_number'] ?? null,
            invoiceDate:        $data['invoice_date'] ?? null,
            items:              $items,
            subtotal:           isset($data['subtotal']) ? (float) $data['subtotal'] : null,
            gstAmount:          isset($data['gst_amount']) ? (float) $data['gst_amount'] : null,
            discountAmount:     isset($data['discount_amount']) ? (float) $data['discount_amount'] : null,
            totalAmount:        isset($data['total_amount']) ? (float) $data['total_amount'] : null,
            metadata:           $data['metadata'] ?? [],
        );
    }
}
