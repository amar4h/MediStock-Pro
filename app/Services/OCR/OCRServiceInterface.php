<?php

namespace App\Services\OCR;

interface OCRServiceInterface
{
    /**
     * Detect and extract text from a document image.
     *
     * @param  string  $imageContent  Raw binary image content (e.g., file_get_contents of image)
     * @return OCRResult
     *
     * @throws OCRException If the OCR service fails or is unavailable
     */
    public function detectDocumentText(string $imageContent): OCRResult;
}
