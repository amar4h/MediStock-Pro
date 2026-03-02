<?php

namespace App\Services\InvoiceParser;

/**
 * Data Transfer Object representing a parsed purchase invoice.
 *
 * All fields are nullable because OCR/parsing may not extract every field
 * from every invoice format.
 */
readonly class ParsedInvoiceDTO
{
    /**
     * @param  string|null                $supplierName        Supplier/distributor name
     * @param  string|null                $supplierGstin       Supplier GSTIN (15-char format)
     * @param  string|null                $supplierDrugLicense Supplier drug license number
     * @param  string|null                $invoiceNumber       Invoice/bill number
     * @param  string|null                $invoiceDate         Invoice date (Y-m-d format)
     * @param  ParsedInvoiceItemDTO[]     $items               Parsed line items
     * @param  float|null                 $subtotal            Subtotal before tax
     * @param  float|null                 $gstAmount           Total GST amount
     * @param  float|null                 $discountAmount      Total discount
     * @param  float|null                 $totalAmount         Grand total / net amount
     * @param  array                      $metadata            Additional extracted data (DL no, phone, etc.)
     */
    public function __construct(
        public ?string $supplierName = null,
        public ?string $supplierGstin = null,
        public ?string $supplierDrugLicense = null,
        public ?string $invoiceNumber = null,
        public ?string $invoiceDate = null,
        public array $items = [],
        public ?float $subtotal = null,
        public ?float $gstAmount = null,
        public ?float $discountAmount = null,
        public ?float $totalAmount = null,
        public array $metadata = [],
    ) {}

    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array
    {
        return [
            'supplier_name'         => $this->supplierName,
            'supplier_gstin'        => $this->supplierGstin,
            'supplier_drug_license' => $this->supplierDrugLicense,
            'invoice_number'        => $this->invoiceNumber,
            'invoice_date'          => $this->invoiceDate,
            'items'                 => array_map(fn($item) => $item->toArray(), $this->items),
            'subtotal'              => $this->subtotal,
            'gst_amount'            => $this->gstAmount,
            'discount_amount'       => $this->discountAmount,
            'total_amount'          => $this->totalAmount,
            'metadata'              => $this->metadata,
        ];
    }

    /**
     * Check if the parsed invoice has any items.
     */
    public function hasItems(): bool
    {
        return ! empty($this->items);
    }

    /**
     * Get the count of parsed items.
     */
    public function itemCount(): int
    {
        return count($this->items);
    }
}
