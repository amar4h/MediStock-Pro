<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BatchService
{
    /**
     * Find available batch(es) for an item using FIFO (nearest expiry first).
     *
     * If a single batch has enough stock, returns it.
     * If not, aggregates multiple batches until the requested quantity is met.
     *
     * @param  int  $itemId    The item to find batches for
     * @param  int  $quantity  The total quantity needed
     * @return Collection<int, array{batch: Batch, allocate: int}>
     *
     * @throws \RuntimeException If insufficient stock is available
     */
    public function findAvailableBatch(int $itemId, int $quantity): Collection
    {
        $batches = Batch::where('item_id', $itemId)
            ->available()     // stock_quantity > 0 AND expiry_date > today
            ->fifoOrder()     // ORDER BY expiry_date ASC
            ->lockForUpdate()
            ->get();

        $totalAvailable = $batches->sum('stock_quantity');

        if ($totalAvailable < $quantity) {
            throw new \RuntimeException(
                "Insufficient stock for item ID {$itemId}. Available: {$totalAvailable}, requested: {$quantity}."
            );
        }

        $remaining = $quantity;
        $allocations = collect();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $allocate = min($batch->stock_quantity, $remaining);

            $allocations->push([
                'batch'    => $batch,
                'allocate' => $allocate,
            ]);

            $remaining -= $allocate;
        }

        return $allocations;
    }

    /**
     * Deduct stock from a batch and create a stock movement record.
     *
     * @param  int     $batchId      The batch to deduct from
     * @param  int     $quantity     The quantity to deduct (positive number)
     * @param  string  $type         Movement type: 'sale', 'sale_return', 'discard', 'adjustment', etc.
     * @param  int     $referenceId  The ID of the source record (sale_id, discard_id, etc.)
     * @return StockMovement
     *
     * @throws \RuntimeException If deduction would result in negative stock
     */
    public function deductStock(int $batchId, int $quantity, string $type, int $referenceId): StockMovement
    {
        $batch = Batch::lockForUpdate()->findOrFail($batchId);

        if ($batch->stock_quantity < $quantity) {
            throw new \RuntimeException(
                "Cannot deduct {$quantity} from batch {$batch->batch_number}. Available: {$batch->stock_quantity}."
            );
        }

        $stockBefore = $batch->stock_quantity;
        $stockAfter = $stockBefore - $quantity;

        $batch->update(['stock_quantity' => $stockAfter]);

        return StockMovement::create([
            'tenant_id'      => $batch->tenant_id,
            'item_id'        => $batch->item_id,
            'batch_id'       => $batch->id,
            'movement_type'  => $type,
            'reference_type' => $type,
            'reference_id'   => $referenceId,
            'quantity'       => -$quantity, // Negative for outgoing stock
            'stock_before'   => $stockBefore,
            'stock_after'    => $stockAfter,
            'created_by'     => Auth::id(),
        ]);
    }

    /**
     * Add stock to a batch and create a stock movement record.
     *
     * Used for purchases, sale returns, and purchase return reversals.
     *
     * @param  int     $batchId      The batch to add stock to
     * @param  int     $quantity     The quantity to add (positive number)
     * @param  string  $type         Movement type: 'purchase', 'sale_return', etc.
     * @param  int     $referenceId  The ID of the source record
     * @return StockMovement
     */
    public function addStock(int $batchId, int $quantity, string $type, int $referenceId): StockMovement
    {
        $batch = Batch::lockForUpdate()->findOrFail($batchId);

        $stockBefore = $batch->stock_quantity;
        $stockAfter = $stockBefore + $quantity;

        $batch->update(['stock_quantity' => $stockAfter]);

        return StockMovement::create([
            'tenant_id'      => $batch->tenant_id,
            'item_id'        => $batch->item_id,
            'batch_id'       => $batch->id,
            'movement_type'  => $type,
            'reference_type' => $type,
            'reference_id'   => $referenceId,
            'quantity'       => $quantity, // Positive for incoming stock
            'stock_before'   => $stockBefore,
            'stock_after'    => $stockAfter,
            'created_by'     => Auth::id(),
        ]);
    }

    /**
     * Create a new batch or update an existing one.
     *
     * Matching is done by tenant_id + item_id + batch_number.
     * If a match is found, stock_quantity is incremented and prices are updated.
     * If no match, a new batch is created.
     *
     * @param  array  $data  Expected keys: tenant_id, item_id, batch_number, expiry_date,
     *                       mrp, purchase_price, selling_price, quantity
     * @return Batch
     */
    public function createOrUpdateBatch(array $data): Batch
    {
        $batch = Batch::withoutGlobalScopes()
            ->where('tenant_id', $data['tenant_id'])
            ->where('item_id', $data['item_id'])
            ->where('batch_number', $data['batch_number'])
            ->lockForUpdate()
            ->first();

        if ($batch) {
            // Update existing batch: increment stock, update prices
            $batch->update([
                'expiry_date'    => $data['expiry_date'],
                'mrp'            => $data['mrp'],
                'purchase_price' => $data['purchase_price'],
                'selling_price'  => $data['selling_price'],
                'stock_quantity' => $batch->stock_quantity + ($data['quantity'] ?? 0),
                'is_active'      => true,
            ]);
        } else {
            // Create new batch
            $batch = Batch::create([
                'tenant_id'      => $data['tenant_id'],
                'item_id'        => $data['item_id'],
                'batch_number'   => $data['batch_number'],
                'expiry_date'    => $data['expiry_date'],
                'mrp'            => $data['mrp'],
                'purchase_price' => $data['purchase_price'],
                'selling_price'  => $data['selling_price'],
                'stock_quantity' => $data['quantity'] ?? 0,
                'is_active'      => true,
            ]);
        }

        return $batch->fresh();
    }

    /**
     * Get all available batches for an item, ordered by FIFO (nearest expiry first).
     *
     * @param  int  $itemId  The item ID
     * @return Collection<int, Batch>
     */
    public function getAvailableBatches(int $itemId): Collection
    {
        return Batch::where('item_id', $itemId)
            ->available()
            ->fifoOrder()
            ->get();
    }
}
