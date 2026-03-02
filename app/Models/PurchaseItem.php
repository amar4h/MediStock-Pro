<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * purchase_items table only has created_at, no updated_at.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'purchase_id',
        'tenant_id',
        'item_id',
        'batch_id',
        'batch_number',
        'expiry_date',
        'quantity',
        'free_quantity',
        'mrp',
        'purchase_price',
        'selling_price',
        'gst_percent',
        'gst_amount',
        'discount_percent',
        'total_amount',
    ];

    protected $casts = [
        'expiry_date'      => 'date',
        'quantity'         => 'integer',
        'free_quantity'    => 'integer',
        'mrp'              => 'decimal:2',
        'purchase_price'   => 'decimal:2',
        'selling_price'    => 'decimal:2',
        'gst_percent'      => 'decimal:2',
        'gst_amount'       => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'total_amount'     => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
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
