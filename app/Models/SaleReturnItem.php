<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItem extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * sale_return_items table only has created_at, no updated_at.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'sale_return_id',
        'tenant_id',
        'item_id',
        'batch_id',
        'quantity',
        'selling_price',
        'total_amount',
    ];

    protected $casts = [
        'quantity'      => 'integer',
        'selling_price' => 'decimal:2',
        'total_amount'  => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
