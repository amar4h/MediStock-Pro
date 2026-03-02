<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * sale_items table only has created_at, no updated_at.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'sale_id',
        'tenant_id',
        'item_id',
        'batch_id',
        'quantity',
        'mrp',
        'selling_price',
        'purchase_price',
        'discount_percent',
        'discount_amount',
        'gst_percent',
        'gst_amount',
        'total_amount',
    ];

    protected $casts = [
        'quantity'         => 'integer',
        'mrp'              => 'decimal:2',
        'selling_price'    => 'decimal:2',
        'purchase_price'   => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'gst_percent'      => 'decimal:2',
        'gst_amount'       => 'decimal:2',
        'total_amount'     => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
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
