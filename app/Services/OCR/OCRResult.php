<?php

namespace App\Services\OCR;

/**
 * Data Transfer Object for OCR results.
 *
 * Represents the extracted text, confidence score, and raw blocks
 * from an OCR service response.
 */
class OCRResult
{
    /**
     * @param  string  $fullText    The complete extracted text from the document
     * @param  float   $confidence  Average confidence score (0.0 to 1.0)
     * @param  array   $blocks      Raw text blocks from the OCR response, each containing:
     *                               - text: string (block text)
     *                               - confidence: float (block-level confidence)
     *                               - boundingBox: array (optional, position data)
     */
    public function __construct(
        public readonly string $fullText,
        public readonly float $confidence,
        public readonly array $blocks = [],
    ) {}

    /**
     * Check if the OCR result has high confidence (>= 0.8).
     */
    public function isHighConfidence(): bool
    {
        return $this->confidence >= 0.8;
    }

    /**
     * Check if the OCR result has usable text.
     */
    public function hasText(): bool
    {
        return trim($this->fullText) !== '';
    }
}
