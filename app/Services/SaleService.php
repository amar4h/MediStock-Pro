<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Sequence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private readonly BatchService $batchService,
    ) {}

    /**
     * Create a sale with items, auto-selecting FIFO batches and deducting stock.
     *
     * This method is atomic — if any step fails, the entire sale is rolled back.
     *
     * @param  array  $data  Expected structure:
     *                       - customer_id: int|null (null = walk-in customer)
     *                       - invoice_date: string (Y-m-d) (optional, defaults to today)
     *                       - payment_mode: 'cash'|'credit'|'partial'|'upi'
     *                       - paid_amount: float (optional, for partial/credit)
     *                       - invoice_discount: float (optional, invoice-level discount)
     *                       - doctor_name: string (optional)
     *                       - patient_name: string (optional)
     *                       - notes: string (optional)
     *                       - items: array[] — each item:
     *                           - item_id: int
     *                           - quantity: int
     *                           - selling_price: float (optional, overrides batch selling_price)
     *                           - discount_percent: float (optional)
     *                       OR with explicit batch:
     *                           - item_id: int
     *                           - batch_id: int
     *                           - quantity: int
     *                           - selling_price: float (optional)
     *                           - discount_percent: float (optional)
     * @param  User   $user  The user creating the sale
     * @return Sale
     *
     * @throws \RuntimeException If stock is insufficient or batches are expired
     */
    public function createSale(array $data, User $user): Sale
    {
        return DB::transaction(function () use ($data, $user) {
            $tenantId = $user->tenant_id;
            $invoiceDate = $data['invoice_date'] ?? Carbon::today()->toDateString();

            // 1. Validate stock availability for all items BEFORE creating anything
            $this->validateStockAvailability($data['items']);

            // 2. Generate invoice number atomically
            $invoiceNumber = Sequence::nextNumber($tenantId, 'sale', 'INV-');

            // 3. Calculate totals and prepare sale items
            $saleItems = [];
            $subtotal = 0;
            $totalGst = 0;
            $totalItemDiscount = 0;

            foreach ($data['items'] as $itemData) {
                $quantity = (int) $itemData['quantity'];
                $discountPercent = (float) ($itemData['discount_percent'] ?? 0);

                if (isset($itemData['batch_id'])) {
                    // Explicit batch specified — use it directly
                    $batch = Batch::lockForUpdate()->findOrFail($itemData['batch_id']);

                    $this->validateBatchForSale($batch, $quantity);

                    $sellingPrice = (float) ($itemData['selling_price'] ?? $batch->selling_price);
                    $gstPercent = $batch->item->gst_percent ?? 0;

                    $lineGross = $quantity * $sellingPrice;
                    $lineDiscount = $lineGross * ($discountPercent / 100);
                    $taxableAmount = $lineGross - $lineDiscount;
                    $lineGst = $taxableAmount * ($gstPercent / 100);
                    $lineTotal = $taxableAmount + $lineGst;

                    $saleItems[] = [
                        'item_id'        => $batch->item_id,
                        'batch_id'       => $batch->id,
                        'quantity'       => $quantity,
                        'mrp'            => $batch->mrp,
                        'selling_price'  => $sellingPrice,
                        'purchase_price' => $batch->purchase_price, // Snapshot for profit
                        'discount_percent' => $discountPercent,
                        'discount_amount'  => round($lineDiscount, 2),
                        'gst_percent'    => $gstPercent,
                        'gst_amount'     => round($lineGst, 2),
                        'total_amount'   => round($lineTotal, 2),
                    ];

                    $subtotal += $taxableAmount;
                    $totalGst += $lineGst;
                    $totalItemDiscount += $lineDiscount;
                } else {
                    // Auto-select FIFO batches
                    $allocations = $this->batchService->findAvailableBatch(
                        $itemData['item_id'],
                        $quantity
                    );

                    foreach ($allocations as $allocation) {
                        /** @var Batch $batch */
                        $batch = $allocation['batch'];
                        $allocateQty = $allocation['allocate'];

                        $this->validateBatchForSale($batch, $allocateQty);

                        $sellingPrice = (float) ($itemData['selling_price'] ?? $batch->selling_price);
                        $gstPercent = $batch->item->gst_percent ?? 0;

                        $lineGross = $allocateQty * $sellingPrice;
                        $lineDiscount = $lineGross * ($discountPercent / 100);
                        $taxableAmount = $lineGross - $lineDiscount;
                        $lineGst = $taxableAmount * ($gstPercent / 100);
                        $lineTotal = $taxableAmount + $lineGst;

                        $saleItems[] = [
                            'item_id'        => $batch->item_id,
                            'batch_id'       => $batch->id,
                            'quantity'       => $allocateQty,
                            'mrp'            => $batch->mrp,
                            'selling_price'  => $sellingPrice,
                            'purchase_price' => $batch->purchase_price,
                            'discount_percent' => $discountPercent,
                            'discount_amount'  => round($lineDiscount, 2),
                            'gst_percent'    => $gstPercent,
                            'gst_amount'     => round($lineGst, 2),
                            'total_amount'   => round($lineTotal, 2),
                        ];

                        $subtotal += $taxableAmount;
                        $totalGst += $lineGst;
                        $totalItemDiscount += $lineDiscount;
                    }
                }
            }

            // 4. Calculate invoice-level totals
            $invoiceDiscount = (float) ($data['invoice_discount'] ?? 0);
            $grandTotal = $subtotal + $totalGst - $invoiceDiscount;

            // Round off to nearest rupee
            $roundoff = round($grandTotal) - $grandTotal;
            $totalAmount = round($grandTotal + $roundoff, 2);

            $paidAmount = (float) ($data['paid_amount'] ?? 0);
            $paymentMode = $data['payment_mode'] ?? 'cash';

            // For cash/UPI, paid = total
            if (in_array($paymentMode, ['cash', 'upi'])) {
                $paidAmount = $totalAmount;
            }

            $balanceAmount = max(0, $totalAmount - $paidAmount);

            // 5. Create the sale header
            $sale = Sale::create([
                'tenant_id'           => $tenantId,
                'customer_id'         => $data['customer_id'] ?? null,
                'invoice_number'      => $invoiceNumber,
                'invoice_date'        => $invoiceDate,
                'subtotal'            => round($subtotal, 2),
                'gst_amount'          => round($totalGst, 2),
                'item_discount_total' => round($totalItemDiscount, 2),
                'invoice_discount'    => round($invoiceDiscount, 2),
                'roundoff'            => round($roundoff, 2),
                'total_amount'        => $totalAmount,
                'payment_mode'        => $paymentMode,
                'paid_amount'         => round($paidAmount, 2),
                'balance_amount'      => round($balanceAmount, 2),
                'status'              => 'completed',
                'doctor_name'         => $data['doctor_name'] ?? null,
                'patient_name'        => $data['patient_name'] ?? null,
                'notes'               => $data['notes'] ?? null,
                'created_by'          => $user->id,
            ]);

            // 6. Create sale items and deduct stock
            foreach ($saleItems as $saleItemData) {
                SaleItem::create([
                    'sale_id'          => $sale->id,
                    'tenant_id'        => $tenantId,
                    'item_id'          => $saleItemData['item_id'],
                    'batch_id'         => $saleItemData['batch_id'],
                    'quantity'         => $saleItemData['quantity'],
                    'mrp'              => $saleItemData['mrp'],
                    'selling_price'    => $saleItemData['selling_price'],
                    'purchase_price'   => $saleItemData['purchase_price'],
                    'discount_percent' => $saleItemData['discount_percent'],
                    'discount_amount'  => $saleItemData['discount_amount'],
                    'gst_percent'      => $saleItemData['gst_percent'],
                    'gst_amount'       => $saleItemData['gst_amount'],
                    'total_amount'     => $saleItemData['total_amount'],
                ]);

                // Deduct stock from batch
                $this->batchService->deductStock(
                    $saleItemData['batch_id'],
                    $saleItemData['quantity'],
                    'sale',
                    $sale->id
                );
            }

            return $sale->load('saleItems.item', 'saleItems.batch', 'customer');
        });
    }

    /**
     * Validate that sufficient stock exists for all sale items.
     *
     * @param  array  $items  The items array from the sale request
     * @throws \RuntimeException If any item has insufficient stock
     */
    private function validateStockAvailability(array $items): void
    {
        foreach ($items as $itemData) {
            $quantity = (int) $itemData['quantity'];

            if (isset($itemData['batch_id'])) {
                $batch = Batch::find($itemData['batch_id']);

                if (! $batch || $batch->stock_quantity < $quantity) {
                    $available = $batch ? $batch->stock_quantity : 0;
                    throw new \RuntimeException(
                        "Insufficient stock for batch ID {$itemData['batch_id']}. Available: {$available}, requested: {$quantity}."
                    );
                }
            } else {
                $totalAvailable = Batch::where('item_id', $itemData['item_id'])
                    ->available()
                    ->sum('stock_quantity');

                if ($totalAvailable < $quantity) {
                    throw new \RuntimeException(
                        "Insufficient stock for item ID {$itemData['item_id']}. Available: {$totalAvailable}, requested: {$quantity}."
                    );
                }
            }
        }
    }

    /**
     * Validate that a batch is eligible for sale (not expired, has stock).
     *
     * @param  Batch  $batch     The batch to validate
     * @param  int    $quantity  The quantity to sell
     * @throws \RuntimeException If the batch is expired or has insufficient stock
     */
    private function validateBatchForSale(Batch $batch, int $quantity): void
    {
        if ($batch->expiry_date->lte(Carbon::today())) {
            throw new \RuntimeException(
                "Cannot sell expired batch {$batch->batch_number} (expired: {$batch->expiry_date->toDateString()})."
            );
        }

        if ($batch->stock_quantity < $quantity) {
            throw new \RuntimeException(
                "Insufficient stock in batch {$batch->batch_number}. Available: {$batch->stock_quantity}, requested: {$quantity}."
            );
        }
    }
}
