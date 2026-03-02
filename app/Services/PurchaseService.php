<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private readonly BatchService $batchService,
    ) {}

    /**
     * Create a purchase record with items, batches, and stock movements.
     *
     * This method is atomic — if any step fails, the entire operation is rolled back.
     *
     * @param  array  $data  Expected structure:
     *                       - supplier_id: int
     *                       - invoice_number: string
     *                       - invoice_date: string (Y-m-d)
     *                       - payment_mode: 'cash'|'credit'|'partial'
     *                       - paid_amount: float (optional)
     *                       - discount_amount: float (optional)
     *                       - notes: string (optional)
     *                       - items: array[] — each item:
     *                           - item_id: int
     *                           - batch_number: string
     *                           - expiry_date: string (Y-m-d)
     *                           - quantity: int
     *                           - free_quantity: int (optional)
     *                           - mrp: float
     *                           - purchase_price: float
     *                           - selling_price: float
     *                           - gst_percent: float (optional)
     *                           - discount_percent: float (optional)
     * @param  User   $user  The user creating the purchase
     * @return Purchase
     */
    public function createPurchase(array $data, User $user): Purchase
    {
        return DB::transaction(function () use ($data, $user) {
            $tenantId = $user->tenant_id;

            // Calculate purchase-level totals
            $subtotal = 0;
            $totalGst = 0;
            $discountAmount = (float) ($data['discount_amount'] ?? 0);

            // Pre-calculate item totals for purchase header
            foreach ($data['items'] as $itemData) {
                $qty = (int) $itemData['quantity'];
                $purchasePrice = (float) $itemData['purchase_price'];
                $gstPercent = (float) ($itemData['gst_percent'] ?? 0);
                $itemDiscountPercent = (float) ($itemData['discount_percent'] ?? 0);

                $lineTotal = $qty * $purchasePrice;
                $lineDiscount = $lineTotal * ($itemDiscountPercent / 100);
                $taxableAmount = $lineTotal - $lineDiscount;
                $lineGst = $taxableAmount * ($gstPercent / 100);

                $subtotal += $taxableAmount;
                $totalGst += $lineGst;
            }

            $totalAmount = $subtotal + $totalGst - $discountAmount;
            $paidAmount = (float) ($data['paid_amount'] ?? 0);

            // For cash purchases, paid = total
            if (($data['payment_mode'] ?? 'cash') === 'cash') {
                $paidAmount = $totalAmount;
            }

            $balanceAmount = $totalAmount - $paidAmount;

            // 1. Create purchase header
            $purchase = Purchase::create([
                'tenant_id'       => $tenantId,
                'supplier_id'     => $data['supplier_id'],
                'invoice_number'  => $data['invoice_number'],
                'invoice_date'    => $data['invoice_date'],
                'subtotal'        => round($subtotal, 2),
                'gst_amount'      => round($totalGst, 2),
                'discount_amount' => round($discountAmount, 2),
                'total_amount'    => round($totalAmount, 2),
                'payment_mode'    => $data['payment_mode'] ?? 'cash',
                'paid_amount'     => round($paidAmount, 2),
                'balance_amount'  => round(max(0, $balanceAmount), 2),
                'notes'           => $data['notes'] ?? null,
                'created_by'      => $user->id,
            ]);

            // 2. Process each purchase item
            foreach ($data['items'] as $itemData) {
                $qty = (int) $itemData['quantity'];
                $freeQty = (int) ($itemData['free_quantity'] ?? 0);
                $purchasePrice = (float) $itemData['purchase_price'];
                $gstPercent = (float) ($itemData['gst_percent'] ?? 0);
                $itemDiscountPercent = (float) ($itemData['discount_percent'] ?? 0);

                $lineTotal = $qty * $purchasePrice;
                $lineDiscount = $lineTotal * ($itemDiscountPercent / 100);
                $taxableAmount = $lineTotal - $lineDiscount;
                $lineGst = $taxableAmount * ($gstPercent / 100);
                $itemTotal = $taxableAmount + $lineGst;

                // 2a. Create or update the batch (quantity + free quantity go into stock)
                $totalBatchQty = $qty + $freeQty;

                $batch = $this->batchService->createOrUpdateBatch([
                    'tenant_id'      => $tenantId,
                    'item_id'        => $itemData['item_id'],
                    'batch_number'   => $itemData['batch_number'],
                    'expiry_date'    => $itemData['expiry_date'],
                    'mrp'            => $itemData['mrp'],
                    'purchase_price' => $purchasePrice,
                    'selling_price'  => $itemData['selling_price'],
                    'quantity'       => $totalBatchQty,
                ]);

                // 2b. Create purchase item record
                PurchaseItem::create([
                    'purchase_id'      => $purchase->id,
                    'tenant_id'        => $tenantId,
                    'item_id'          => $itemData['item_id'],
                    'batch_id'         => $batch->id,
                    'batch_number'     => $itemData['batch_number'],
                    'expiry_date'      => $itemData['expiry_date'],
                    'quantity'         => $qty,
                    'free_quantity'    => $freeQty,
                    'mrp'              => $itemData['mrp'],
                    'purchase_price'   => $purchasePrice,
                    'selling_price'    => $itemData['selling_price'],
                    'gst_percent'      => $gstPercent,
                    'gst_amount'       => round($lineGst, 2),
                    'discount_percent' => $itemDiscountPercent,
                    'total_amount'     => round($itemTotal, 2),
                ]);

                // 2c. Create stock movement for the batch addition
                $this->batchService->addStock(
                    $batch->id,
                    $totalBatchQty,
                    'purchase',
                    $purchase->id
                );
            }

            return $purchase->load('purchaseItems.item', 'purchaseItems.batch', 'supplier');
        });
    }
}
