<?php

namespace App\Services\InvoiceParser;

use Illuminate\Support\Facades\Log;

/**
 * Fallback invoice parser that tries the primary parser first,
 * then falls back to the secondary parser on failure.
 *
 * This ensures invoice parsing always has the best chance of success:
 * - Primary: LLM-based parser (Claude Haiku) — high accuracy
 * - Fallback: Regex-based parser — lower accuracy but always available
 */
class FallbackInvoiceParser implements InvoiceParserInterface
{
    public function __construct(
        private readonly InvoiceParserInterface $primary,
        private readonly InvoiceParserInterface $fallback,
    ) {}

    /**
     * Parse OCR text using the primary parser, falling back to the secondary on failure.
     *
     * @param  string  $ocrText  Raw OCR text
     * @return ParsedInvoiceDTO
     *
     * @throws ParsingException If both parsers fail
     */
    public function parse(string $ocrText): ParsedInvoiceDTO
    {
        try {
            return $this->primary->parse($ocrText);
        } catch (\Exception $primaryException) {
            Log::warning('Primary invoice parser failed, falling back to secondary parser.', [
                'primary_error'  => $primaryException->getMessage(),
                'primary_class'  => get_class($this->primary),
                'fallback_class' => get_class($this->fallback),
            ]);

            try {
                $result = $this->fallback->parse($ocrText);

                // Add metadata indicating fallback was used
                return new ParsedInvoiceDTO(
                    supplierName:       $result->supplierName,
                    supplierGstin:      $result->supplierGstin,
                    supplierDrugLicense: $result->supplierDrugLicense,
                    invoiceNumber:      $result->invoiceNumber,
                    invoiceDate:        $result->invoiceDate,
                    items:              $result->items,
                    subtotal:           $result->subtotal,
                    gstAmount:          $result->gstAmount,
                    discountAmount:     $result->discountAmount,
                    totalAmount:        $result->totalAmount,
                    metadata:           array_merge($result->metadata, [
                        'used_fallback'       => true,
                        'primary_error'       => $primaryException->getMessage(),
                    ]),
                );
            } catch (\Exception $fallbackException) {
                Log::error('Both primary and fallback invoice parsers failed.', [
                    'primary_error'  => $primaryException->getMessage(),
                    'fallback_error' => $fallbackException->getMessage(),
                ]);

                throw new ParsingException(
                    "All parsers failed. Primary: {$primaryException->getMessage()}, Fallback: {$fallbackException->getMessage()}",
                    0,
                    $primaryException
                );
            }
        }
    }
}
