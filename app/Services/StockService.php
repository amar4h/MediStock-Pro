<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Item;
use App\Models\StockDiscard;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(
        private readonly BatchService $batchService,
    ) {}

    /**
     * Create a stock movement record.
     *
     * @param  array  $data  Expected keys: tenant_id, item_id, batch_id, movement_type,
     *                       reference_type, reference_id, quantity, stock_before, stock_after,
     *                       created_by (optional)
     * @return StockMovement
     */
    public function createMovement(array $data): StockMovement
    {
        return StockMovement::create([
            'tenant_id'      => $data['tenant_id'],
            'item_id'        => $data['item_id'],
            'batch_id'       => $data['batch_id'],
            'movement_type'  => $data['movement_type'],
            'reference_type' => $data['reference_type'],
            'reference_id'   => $data['reference_id'],
            'quantity'       => $data['quantity'],
            'stock_before'   => $data['stock_before'],
            'stock_after'    => $data['stock_after'],
            'created_by'     => $data['created_by'] ?? null,
        ]);
    }

    /**
     * Get items where total batch stock is below the low-stock threshold.
     *
     * Uses the tenant's configured threshold from settings, or a default of 10.
     *
     * @param  int       $tenantId
     * @param  int|null  $threshold  Override the tenant-configured threshold
     * @return Collection<int, Item>
     */
    public function getLowStockItems(int $tenantId, ?int $threshold = null): Collection
    {
        if ($threshold === null) {
            $tenant = \App\Models\Tenant::find($tenantId);
            $threshold = $tenant?->settings['low_stock_threshold'] ?? 10;
        }

        return Item::withoutGlobalScopes()
            ->where('items.tenant_id', $tenantId)
            ->where('items.is_active', true)
            ->whereNull('items.deleted_at')
            ->select('items.*')
            ->selectRaw('COALESCE(SUM(batches.stock_quantity), 0) as total_stock')
            ->leftJoin('batches', function ($join) use ($tenantId) {
                $join->on('batches.item_id', '=', 'items.id')
                     ->where('batches.tenant_id', '=', $tenantId)
                     ->where('batches.is_active', '=', true);
            })
            ->groupBy('items.id')
            ->havingRaw('COALESCE(SUM(batches.stock_quantity), 0) < ?', [$threshold])
            ->orderByRaw('COALESCE(SUM(batches.stock_quantity), 0) ASC')
            ->get();
    }

    /**
     * Get batches expiring within the specified number of days.
     *
     * @param  int  $tenantId
     * @param  int  $days  Number of days from today (default: 30)
     * @return Collection<int, Batch>
     */
    public function getNearExpiryBatches(int $tenantId, int $days = 30): Collection
    {
        return Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('stock_quantity', '>', 0)
            ->where('expiry_date', '>', Carbon::today())
            ->where('expiry_date', '<=', Carbon::today()->addDays($days))
            ->with('item')
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get expired batches that still have stock > 0.
     *
     * @param  int  $tenantId
     * @return Collection<int, Batch>
     */
    public function getExpiredBatches(int $tenantId): Collection
    {
        return Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('stock_quantity', '>', 0)
            ->where('expiry_date', '<=', Carbon::today())
            ->with('item')
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get dead stock: items with no stock movement in the specified number of days.
     *
     * @param  int  $tenantId
     * @param  int  $days  Number of days without movement (default: 90)
     * @return Collection<int, Item>
     */
    public function getDeadStock(int $tenantId, int $days = 90): Collection
    {
        $cutoffDate = Carbon::today()->subDays($days);

        return Item::withoutGlobalScopes()
            ->where('items.tenant_id', $tenantId)
            ->where('items.is_active', true)
            ->whereNull('items.deleted_at')
            ->select('items.*')
            ->selectRaw('COALESCE(SUM(batches.stock_quantity), 0) as total_stock')
            ->selectRaw('MAX(stock_movements.created_at) as last_movement_at')
            ->leftJoin('batches', function ($join) use ($tenantId) {
                $join->on('batches.item_id', '=', 'items.id')
                     ->where('batches.tenant_id', '=', $tenantId);
            })
            ->leftJoin('stock_movements', function ($join) use ($tenantId) {
                $join->on('stock_movements.item_id', '=', 'items.id')
                     ->where('stock_movements.tenant_id', '=', $tenantId);
            })
            ->groupBy('items.id')
            ->havingRaw('COALESCE(SUM(batches.stock_quantity), 0) > 0')
            ->having(function ($query) use ($cutoffDate) {
                $query->havingRaw('MAX(stock_movements.created_at) < ?', [$cutoffDate])
                      ->orHavingRaw('MAX(stock_movements.created_at) IS NULL');
            })
            ->orderByRaw('MAX(stock_movements.created_at) ASC')
            ->get();
    }

    /**
     * Discard stock from a batch (expired, damaged, lost, etc.).
     *
     * Creates a StockDiscard record and deducts the stock from the batch.
     *
     * @param  array  $data  Expected keys: item_id, batch_id, quantity, reason, notes (optional), discard_date
     * @param  User   $user  The user performing the discard
     * @return StockDiscard
     */
    public function discardStock(array $data, User $user): StockDiscard
    {
        return DB::transaction(function () use ($data, $user) {
            // Create the discard record
            $discard = StockDiscard::create([
                'tenant_id'    => $user->tenant_id,
                'item_id'      => $data['item_id'],
                'batch_id'     => $data['batch_id'],
                'quantity'     => $data['quantity'],
                'reason'       => $data['reason'],
                'notes'        => $data['notes'] ?? null,
                'created_by'   => $user->id,
                'discard_date' => $data['discard_date'] ?? Carbon::today()->toDateString(),
            ]);

            // Deduct stock from batch and create movement
            $this->batchService->deductStock(
                $data['batch_id'],
                $data['quantity'],
                'discard',
                $discard->id
            );

            return $discard->load('item', 'batch');
        });
    }
}
