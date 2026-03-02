<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'item_id',
        'batch_number',
        'expiry_date',
        'mrp',
        'purchase_price',
        'selling_price',
        'stock_quantity',
        'is_active',
    ];

    protected $casts = [
        'expiry_date'    => 'date',
        'mrp'            => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'selling_price'  => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_active'      => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // ── Scopes ───────────────────────────────────────────────────

    /**
     * Batches with stock > 0 and not yet expired.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('stock_quantity', '>', 0)
                     ->where('expiry_date', '>', Carbon::today());
    }

    /**
     * Batches expiring within the given number of days.
     */
    public function scopeNearExpiry(Builder $query, int $days = 30): Builder
    {
        return $query->where('stock_quantity', '>', 0)
                     ->where('expiry_date', '>', Carbon::today())
                     ->where('expiry_date', '<=', Carbon::today()->addDays($days));
    }

    /**
     * Batches that have already expired.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expiry_date', '<=', Carbon::today());
    }

    /**
     * Order batches by expiry_date ASC for FIFO selection.
     */
    public function scopeFifoOrder(Builder $query): Builder
    {
        return $query->orderBy('expiry_date', 'asc');
    }
}
