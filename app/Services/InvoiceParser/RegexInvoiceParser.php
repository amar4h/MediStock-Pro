<?php

namespace App\Services\InvoiceParser;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Regex-based fallback invoice parser.
 *
 * Extracts structured data from OCR text using regular expressions and
 * heuristic rules. Works as a fallback when the LLM parser is unavailable.
 *
 * Handles common Indian pharmacy invoice formats with patterns for:
 * - GSTIN, drug license, invoice numbers
 * - Table header detection and line-by-line item extraction
 * - Multiple date formats (MM/YY, MM/YYYY, MON-YY)
 */
class RegexInvoiceParser implements InvoiceParserInterface
{
    // Common month abbreviations used in Indian invoices
    private const MONTH_MAP = [
        'JAN' => '01', 'FEB' => '02', 'MAR' => '03', 'APR' => '04',
        'MAY' => '05', 'JUN' => '06', 'JUL' => '07', 'AUG' => '08',
        'SEP' => '09', 'OCT' => '10', 'NOV' => '11', 'DEC' => '12',
    ];

    /**
     * Parse OCR text using regex-based extraction.
     *
     * @param  string  $ocrText  Raw OCR text
     * @return ParsedInvoiceDTO
     *
     * @throws ParsingException If parsing yields no usable data
     */
    public function parse(string $ocrText): ParsedInvoiceDTO
    {
        if (empty(trim($ocrText))) {
            throw ParsingException::regexFailure('Empty OCR text provided');
        }

        $lines = preg_split('/\r?\n/', $ocrText);

        $supplierName = $this->extractSupplierName($lines);
        $supplierGstin = $this->extractGstin($ocrText);
        $supplierDrugLicense = $this->extractDrugLicense($ocrText);
        $invoiceNumber = $this->extractInvoiceNumber($ocrText);
        $invoiceDate = $this->extractInvoiceDate($ocrText);
        $items = $this->extractItems($lines);
        $totals = $this->extractTotals($ocrText);

        return new ParsedInvoiceDTO(
            supplierName:       $supplierName,
            supplierGstin:      $supplierGstin,
            supplierDrugLicense: $supplierDrugLicense,
            invoiceNumber:      $invoiceNumber,
            invoiceDate:        $invoiceDate,
            items:              $items,
            subtotal:           $totals['subtotal'],
            gstAmount:          $totals['gst_amount'],
            discountAmount:     $totals['discount_amount'],
            totalAmount:        $totals['total_amount'],
            metadata:           [
                'parser' => 'regex',
            ],
        );
    }

    /**
     * Extract supplier name (usually the first prominent line of the invoice).
     */
    private function extractSupplierName(array $lines): ?string
    {
        // The supplier name is typically one of the first non-empty lines
        // before any GSTIN or address information
        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (empty($trimmed) || mb_strlen($trimmed) < 3) {
                continue;
            }

            // Skip lines that are clearly not names
            if (preg_match('/^(GSTIN|GST|DL|Drug|Invoice|Bill|Tax|Ph|Tel|Fax|Email|Date|No\.|Mob)/i', $trimmed)) {
                continue;
            }

            // Skip lines that are mostly numbers
            if (preg_match('/^\d[\d\s\-\/\.]+$/', $trimmed)) {
                continue;
            }

            // Return the first meaningful text line (likely supplier name)
            return $trimmed;
        }

        return null;
    }

    /**
     * Extract GSTIN using the standard 15-character alphanumeric pattern.
     * Format: 2-digit state code + 10-char PAN + 1 entity + 1 check + 1 default Z
     */
    private function extractGstin(string $text): ?string
    {
        if (preg_match('/\b(\d{2}[A-Z]{5}\d{4}[A-Z]\d[A-Z\d][A-Z])\b/', strtoupper($text), $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract drug license number.
     * Common formats: MH-MUM-123456, DL-20B-12345, 20B/MUM/12345
     */
    private function extractDrugLicense(string $text): ?string
    {
        $patterns = [
            '/(?:D\.?L\.?\s*(?:No\.?)?\s*[:.]?\s*)([A-Z]{2}[\-\/][A-Z0-9\-\/]+)/i',
            '/(?:Drug\s*Lic(?:ence|ense)?\.?\s*(?:No\.?)?\s*[:.]?\s*)([A-Z0-9\-\/]+)/i',
            '/\b(\d{2}[A-Z]\/[A-Z]{3}\/\d+)\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Extract invoice number from common patterns.
     */
    private function extractInvoiceNumber(string $text): ?string
    {
        $patterns = [
            '/(?:Invoice|Inv|Bill)\s*(?:No\.?|Number|#)\s*[:.]?\s*([A-Z0-9\-\/]+)/i',
            '/(?:Ref\.?\s*(?:No\.?)?\s*[:.]?\s*)([A-Z0-9\-\/]+)/i',
            '/(?:Voucher\s*(?:No\.?)?\s*[:.]?\s*)([A-Z0-9\-\/]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Extract invoice date and normalize to Y-m-d format.
     */
    private function extractInvoiceDate(string $text): ?string
    {
        $patterns = [
            // "Date: 15/01/2025" or "Date: 15-01-2025"
            '/(?:Date|Dt\.?|Invoice\s*Date)\s*[:.]?\s*(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})/i',
            // "Date: 15 Jan 2025"
            '/(?:Date|Dt\.?)\s*[:.]?\s*(\d{1,2}\s+[A-Za-z]{3,9}\s+\d{2,4})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $this->normalizeDate($matches[1]);
            }
        }

        return null;
    }

    /**
     * Extract line items from the invoice text.
     *
     * Strategy:
     * 1. Detect the table header row by looking for keywords (Qty, MRP, Rate, Batch, etc.)
     * 2. Parse subsequent lines as item rows until a totals/summary section is reached
     */
    private function extractItems(array $lines): array
    {
        $items = [];
        $headerIndex = $this->findTableHeaderIndex($lines);

        if ($headerIndex === null) {
            return $items;
        }

        $rowIndex = 0;

        // Process lines after the header
        for ($i = $headerIndex + 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            // Stop at totals/summary section
            if ($this->isTotalsLine($line)) {
                break;
            }

            // Try to parse the line as an item row
            $item = $this->parseItemLine($line, $rowIndex);
            if ($item !== null) {
                $items[] = $item;
                $rowIndex++;
            }
        }

        return $items;
    }

    /**
     * Find the table header row index by matching common column keywords.
     */
    private function findTableHeaderIndex(array $lines): ?int
    {
        $headerKeywords = ['qty', 'mrp', 'rate', 'batch', 'expiry', 'amount', 'pack', 'item', 'product', 'particular'];

        foreach ($lines as $index => $line) {
            $lower = strtolower($line);
            $matchCount = 0;

            foreach ($headerKeywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $matchCount++;
                }
            }

            // If at least 3 header keywords found, this is likely the header row
            if ($matchCount >= 3) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Check if a line is a totals/summary line (end of items section).
     */
    private function isTotalsLine(string $line): bool
    {
        $totalsKeywords = ['total', 'subtotal', 'sub total', 'grand total', 'net amount', 'round off', 'cgst', 'sgst', 'igst'];
        $lower = strtolower($line);

        foreach ($totalsKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse a single line as an invoice item row.
     *
     * Uses heuristic patterns to extract fields from various column layouts.
     */
    private function parseItemLine(string $line, int $rowIndex): ?ParsedInvoiceItemDTO
    {
        // Skip lines that are clearly separator lines or too short
        if (preg_match('/^[\-=_\*\.]+$/', $line) || mb_strlen($line) < 10) {
            return null;
        }

        // Split by 2+ spaces or tabs (column separators in OCR output)
        $parts = preg_split('/\s{2,}|\t/', $line);
        $parts = array_values(array_filter($parts, fn($p) => trim($p) !== ''));

        if (count($parts) < 3) {
            return null;
        }

        // Heuristic extraction based on part content
        $itemName = null;
        $batchNumber = null;
        $expiryDate = null;
        $mrp = null;
        $purchasePrice = null;
        $quantity = null;
        $freeQuantity = null;
        $gstPercent = null;
        $discountPercent = null;
        $hsnCode = null;
        $packSize = null;

        foreach ($parts as $partIndex => $part) {
            $part = trim($part);

            // Skip the serial number (first column, single digit)
            if ($partIndex === 0 && preg_match('/^\d{1,3}$/', $part)) {
                continue;
            }

            // Item name: first non-numeric multi-word part
            if ($itemName === null && preg_match('/[a-zA-Z]{2,}/', $part) && ! preg_match('/^\d/', $part)) {
                $itemName = $part;
                continue;
            }

            // Batch number: alphanumeric pattern
            if ($batchNumber === null && preg_match('/^[A-Z0-9][\w\-]{2,}$/i', $part) && $itemName !== null) {
                $batchNumber = $part;
                continue;
            }

            // Expiry date patterns
            if ($expiryDate === null && preg_match('/(\d{1,2}[\/-]\d{2,4})|([A-Z]{3}[\/-]\d{2,4})/i', $part)) {
                $expiryDate = $this->normalizeExpiryDate($part);
                continue;
            }

            // Pack size
            if ($packSize === null && preg_match('/^\d+\s*[xX]\s*\d+$/', $part)) {
                $packSize = $part;
                continue;
            }

            // HSN code: 4-8 digit number
            if ($hsnCode === null && preg_match('/^\d{4,8}$/', $part) && (int) $part >= 3001 && (int) $part <= 9999) {
                $hsnCode = $part;
                continue;
            }

            // Numeric values (quantity, prices, percentages)
            $numericValue = $this->normalizeAmount($part);
            if ($numericValue !== null) {
                // Assign to fields in order of likelihood
                if ($quantity === null && $numericValue == (int) $numericValue && $numericValue > 0 && $numericValue <= 9999) {
                    $quantity = (int) $numericValue;
                } elseif ($freeQuantity === null && $numericValue == (int) $numericValue && $numericValue >= 0 && $numericValue <= 999 && $quantity !== null) {
                    $freeQuantity = (int) $numericValue;
                } elseif ($purchasePrice === null && $numericValue > 0) {
                    $purchasePrice = $numericValue;
                } elseif ($mrp === null && $numericValue > 0) {
                    $mrp = $numericValue;
                } elseif ($discountPercent === null && $numericValue >= 0 && $numericValue <= 100) {
                    $discountPercent = $numericValue;
                } elseif ($gstPercent === null && in_array($numericValue, [0, 5, 12, 18, 28])) {
                    $gstPercent = $numericValue;
                }
            }
        }

        // Must have at least an item name to be considered a valid row
        if ($itemName === null) {
            return null;
        }

        $confidence = [];
        if ($itemName !== null) $confidence['item_name'] = 'medium';
        if ($batchNumber !== null) $confidence['batch_number'] = 'medium';
        if ($expiryDate !== null) $confidence['expiry_date'] = 'medium';
        if ($mrp !== null) $confidence['mrp'] = 'low';
        if ($purchasePrice !== null) $confidence['purchase_price'] = 'low';
        if ($quantity !== null) $confidence['quantity'] = 'medium';

        return new ParsedInvoiceItemDTO(
            rowIndex:        $rowIndex,
            itemName:        $itemName,
            batchNumber:     $batchNumber,
            expiryDate:      $expiryDate,
            mrp:             $mrp,
            purchasePrice:   $purchasePrice,
            quantity:        $quantity,
            freeQuantity:    $freeQuantity,
            gstPercent:      $gstPercent,
            discountPercent: $discountPercent,
            hsnCode:         $hsnCode,
            packSize:        $packSize,
            fieldConfidence: $confidence,
        );
    }

    /**
     * Extract total amounts from the invoice footer.
     */
    private function extractTotals(string $text): array
    {
        $totals = [
            'subtotal'        => null,
            'gst_amount'      => null,
            'discount_amount' => null,
            'total_amount'    => null,
        ];

        // Subtotal
        if (preg_match('/(?:Sub\s*Total|Subtotal)\s*[:.]?\s*([\d,]+\.?\d*)/i', $text, $m)) {
            $totals['subtotal'] = $this->normalizeAmount($m[1]);
        }

        // Total GST (CGST + SGST or IGST)
        $gstTotal = 0;
        if (preg_match('/(?:CGST|C\.G\.S\.T)\s*[:.]?\s*([\d,]+\.?\d*)/i', $text, $m)) {
            $gstTotal += $this->normalizeAmount($m[1]) ?? 0;
        }
        if (preg_match('/(?:SGST|S\.G\.S\.T)\s*[:.]?\s*([\d,]+\.?\d*)/i', $text, $m)) {
            $gstTotal += $this->normalizeAmount($m[1]) ?? 0;
        }
        if (preg_match('/(?:IGST|I\.G\.S\.T)\s*[:.]?\s*([\d,]+\.?\d*)/i', $text, $m)) {
            $gstTotal += $this->normalizeAmount($m[1]) ?? 0;
        }
        if ($gstTotal > 0) {
            $totals['gst_amount'] = $gstTotal;
        }

        // Discount
        if (preg_match('/(?:Discount|Disc\.?)\s*[:.]?\s*([\d,]+\.?\d*)/i', $text, $m)) {
            $totals['discount_amount'] = $this->normalizeAmount($m[1]);
        }

        // Grand total / Net amount
        $totalPatterns = [
            '/(?:Grand\s*Total|Net\s*Amount|Net\s*Payable|Total\s*Amount)\s*[:.]?\s*([\d,]+\.?\d*)/i',
            '/(?:Total)\s*[:.]?\s*([\d,]+\.?\d*)/i',
        ];
        foreach ($totalPatterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $totals['total_amount'] = $this->normalizeAmount($m[1]);
                break;
            }
        }

        return $totals;
    }

    /**
     * Normalize a date string to Y-m-d format.
     */
    private function normalizeDate(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);

        // DD/MM/YYYY or DD-MM-YYYY
        if (preg_match('/(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})/', $dateStr, $m)) {
            try {
                return Carbon::createFromFormat('d-m-Y', "{$m[1]}-{$m[2]}-{$m[3]}")->toDateString();
            } catch (\Exception $e) {
                // Fall through
            }
        }

        // DD/MM/YY or DD-MM-YY
        if (preg_match('/(\d{1,2})[\/-](\d{1,2})[\/-](\d{2})$/', $dateStr, $m)) {
            $year = ((int) $m[3] < 50) ? '20' . $m[3] : '19' . $m[3];
            try {
                return Carbon::createFromFormat('d-m-Y', "{$m[1]}-{$m[2]}-{$year}")->toDateString();
            } catch (\Exception $e) {
                // Fall through
            }
        }

        // "15 Jan 2025" format
        if (preg_match('/(\d{1,2})\s+([A-Za-z]{3,9})\s+(\d{2,4})/', $dateStr, $m)) {
            try {
                return Carbon::parse($dateStr)->toDateString();
            } catch (\Exception $e) {
                // Fall through
            }
        }

        return null;
    }

    /**
     * Normalize expiry date to Y-m-d format.
     *
     * Expiry dates only have month and year. We set day to the last day of the month.
     * Supports: MM/YY, MM/YYYY, MON-YY, MON-YYYY
     */
    private function normalizeExpiryDate(string $dateStr): ?string
    {
        $dateStr = trim(strtoupper($dateStr));

        // MM/YY or MM/YYYY
        if (preg_match('/^(\d{1,2})[\/-](\d{2,4})$/', $dateStr, $m)) {
            $month = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $year = $m[2];
            if (strlen($year) === 2) {
                $year = ((int) $year < 50) ? '20' . $year : '19' . $year;
            }

            try {
                return Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-01")
                    ->endOfMonth()
                    ->toDateString();
            } catch (\Exception $e) {
                return null;
            }
        }

        // MON-YY or MON/YY or MON-YYYY
        if (preg_match('/^([A-Z]{3})[\/-](\d{2,4})$/', $dateStr, $m)) {
            $monthNum = self::MONTH_MAP[$m[1]] ?? null;
            if ($monthNum === null) {
                return null;
            }

            $year = $m[2];
            if (strlen($year) === 2) {
                $year = ((int) $year < 50) ? '20' . $year : '19' . $year;
            }

            try {
                return Carbon::createFromFormat('Y-m-d', "{$year}-{$monthNum}-01")
                    ->endOfMonth()
                    ->toDateString();
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Normalize an amount string by stripping commas and currency symbols.
     */
    private function normalizeAmount(string $value): ?float
    {
        // Remove currency symbols, commas, and whitespace
        $cleaned = preg_replace('/[₹Rs\.\s,]/', '', trim($value));

        if (is_numeric($cleaned)) {
            return (float) $cleaned;
        }

        return null;
    }
}
