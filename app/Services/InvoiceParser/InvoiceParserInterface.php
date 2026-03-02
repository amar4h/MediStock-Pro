<?php

namespace App\Services\InvoiceParser;

interface InvoiceParserInterface
{
    /**
     * Parse OCR text into a structured invoice DTO.
     *
     * @param  string  $ocrText  The raw text extracted from OCR
     * @return ParsedInvoiceDTO
     *
     * @throws ParsingException If parsing fails
     */
    public function parse(string $ocrText): ParsedInvoiceDTO;
}
