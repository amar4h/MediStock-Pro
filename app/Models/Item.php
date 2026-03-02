<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, BelongsToTenant, Auditable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'manufacturer_id',
        'name',
        'composition',
        'hsn_code',
        'gst_percent',
        'default_margin',
        'barcode',
        'unit',
        'schedule',
        'is_active',
    ];

    protected $casts = [
        'gst_percent'    => 'decimal:2',
        'default_margin' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
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

    public function stockDiscards(): HasMany
    {
        return $this->hasMany(StockDiscard::class);
    }

    // ── Scopes ───────────────────────────────────────────────────

    // ── Accessors ───────────────────────────────────────────────

    public function getCurrentStockAttribute(): int
    {
        return (int) ($this->total_stock ?? $this->batches()->sum('stock_quantity'));
    }

    public function getStatusAttribute(): string
    {
        return $this->is_active ? 'active' : 'inactive';
    }

    /**
     * FULLTEXT search scope for item name and composition.
     * Falls back to LIKE search if FULLTEXT is not available.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->whereRaw(
                'MATCH(name, composition) AGAINST(? IN BOOLEAN MODE)',
                [$term . '*']
            )->orWhere('name', 'LIKE', "%{$term}%")
              ->orWhere('composition', 'LIKE', "%{$term}%")
              ->orWhere('barcode', $term);
        });
    }
}
