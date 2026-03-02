<?php

namespace App\Services\InvoiceParser;

/**
 * Data Transfer Object for a single parsed invoice line item.
 *
 * All fields are nullable because OCR/parsing may not capture every column
 * for every item row.
 */
readonly class ParsedInvoiceItemDTO
{
    /**
     * @param  int         $rowIndex         Row index in the parsed table (0-based)
     * @param  string|null $itemName         Item/product name
     * @param  string|null $batchNumber      Batch/lot number
     * @param  string|null $expiryDate       Expiry date (Y-m-d format, normalized)
     * @param  float|null  $mrp              Maximum retail price
     * @param  float|null  $purchasePrice    Purchase/trade price per unit
     * @param  int|null    $quantity         Quantity purchased
     * @param  int|null    $freeQuantity     Free/bonus quantity
     * @param  float|null  $gstPercent       GST percentage
     * @param  float|null  $discountPercent  Discount percentage
     * @param  string|null $hsnCode          HSN/SAC code
     * @param  string|null $packSize         Pack size (e.g., '10x10', '100ml')
     * @param  array       $fieldConfidence  Per-field confidence scores: ['field_name' => 'high'|'medium'|'low']
     */
    public function __construct(
        public int $rowIndex = 0,
        public ?string $itemName = null,
        public ?string $batchNumber = null,
        public ?string $expiryDate = null,
        public ?float $mrp = null,
        public ?float $purchasePrice = null,
        public ?int $quantity = null,
        public ?int $freeQuantity = null,
        public ?float $gstPercent = null,
        public ?float $discountPercent = null,
        public ?string $hsnCode = null,
        public ?string $packSize = null,
        public array $fieldConfidence = [],
    ) {}

    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array
    {
        return [
            'row_index'        => $this->rowIndex,
            'item_name'        => $this->itemName,
            'batch_number'     => $this->batchNumber,
            'expiry_date'      => $this->expiryDate,
            'mrp'              => $this->mrp,
            'purchase_price'   => $this->purchasePrice,
            'quantity'         => $this->quantity,
            'free_quantity'    => $this->freeQuantity,
            'gst_percent'      => $this->gstPercent,
            'discount_percent' => $this->discountPercent,
            'hsn_code'         => $this->hsnCode,
            'pack_size'        => $this->packSize,
            'field_confidence' => $this->fieldConfidence,
        ];
    }

    /**
     * Check if the essential fields are present (name + quantity + price).
     */
    public function hasEssentialFields(): bool
    {
        return $this->itemName !== null
            && $this->quantity !== null
            && $this->purchasePrice !== null;
    }
}
